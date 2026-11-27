<?php

namespace App\Http\Controllers\StorePortal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStoreEarning;
use App\Models\BookingStoreHandover;
use App\Models\StoreBankAccount;
use App\Models\StoreTransaction;
use App\Models\StoreUser;
use App\Services\CurrencyService;
use App\Services\StoreAccountService;
use App\Services\StoreBankAccountService;
use App\Services\StoreEarningsService;
use App\Services\StoreHandoverService;
use App\Services\StoreOperationalStatusService;
use App\Services\StoreWithdrawalService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class StoreDashboardController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected StoreEarningsService $earningsService,
        protected StoreAccountService $accountService,
        protected StoreWithdrawalService $withdrawalService,
        protected StoreBankAccountService $bankService
    ) {}

    protected function storeUser(): StoreUser
    {
        /** @var StoreUser $user */
        $user = Auth::guard('store')->user();
        $user->loadMissing('store.vendor');

        return $user;
    }

    protected function storeBookingsBaseQuery(int $storeId): Builder
    {
        return Booking::query()
            ->whereNotNull('confirmed_at')
            ->whereHas('currentStoreAssignment', fn ($q) => $q->where('store_id', $storeId));
    }

    protected function resolveMonthPeriod(Request $request): array
    {
        $tz = (string) config('app.timezone', 'UTC');
        $nowTz = Carbon::now($tz);

        $reqMonth = $request->input('month');
        $reqYear = $request->input('year');
        $selectedMonth = is_numeric($reqMonth) && (int) $reqMonth >= 1 && (int) $reqMonth <= 12
            ? (int) $reqMonth
            : $nowTz->month;
        $selectedYear = is_numeric($reqYear) && (int) $reqYear >= 2020 && (int) $reqYear <= 2099
            ? (int) $reqYear
            : $nowTz->year;

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, $tz);
        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow()->startOfMonth();
        $prevMonthDate = $monthStart->copy()->subMonthNoOverflow();
        $nextMonthDate = $monthStart->copy()->addMonthNoOverflow();

        return [
            'tz' => $tz,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthLabel' => $monthStart->format('F Y'),
            'monthStart' => $monthStart,
            'nextMonthStart' => $nextMonthStart,
            'monthStartUtc' => $monthStart->copy()->utc()->toDateTimeString(),
            'nextMonthStartUtc' => $nextMonthStart->copy()->utc()->toDateTimeString(),
            'prevMonth' => $prevMonthDate->month,
            'prevYear' => $prevMonthDate->year,
            'nextMonth' => $nextMonthDate->month,
            'nextYear' => $nextMonthDate->year,
            'currentMonth' => $nowTz->month,
            'currentYear' => $nowTz->year,
        ];
    }

    public function dashboard(Request $request): View|JsonResponse
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $period = $this->resolveMonthPeriod($request);

        $startStr = $period['monthStartUtc'];
        $endStr = $period['nextMonthStartUtc'];

        // Monthly Assigned Bookings (filtered by Store assignment date)
        $monthlyAssigned = $this->storeBookingsBaseQuery($store->id)
            ->whereHas('currentStoreAssignment', function ($q) use ($startStr, $endStr) {
                $q->whereRaw('COALESCE(assigned_at, created_at) >= ? AND COALESCE(assigned_at, created_at) < ?', [$startStr, $endStr]);
            })
            ->with(['currentStoreAssignment', 'currentStoreEarning', 'operationalItems'])
            ->get();

        $monthlyStats = [
            'assigned' => $monthlyAssigned->count(),
            'received' => 0,
            'processing' => 0,
            'completed' => 0,
        ];

        $dailyChart = [];
        foreach ($monthlyAssigned as $bk) {
            $stages = is_array($bk->completed_operational_stages) ? $bk->completed_operational_stages : [];
            if (in_array(Booking::STATUS_STORE_RECEIVED, $stages, true) || $bk->booking_status === Booking::STATUS_STORE_RECEIVED) {
                $monthlyStats['received']++;
            }
            if ($bk->booking_status === Booking::STATUS_STORE_PROCESSING) {
                $monthlyStats['processing']++;
            }
            if (in_array(Booking::STATUS_STORE_COMPLETE, $stages, true) || in_array($bk->booking_status, [Booking::STATUS_STORE_COMPLETE, Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP, Booking::STATUS_ON_DELIVERY, Booking::STATUS_COMPLETED], true)) {
                $monthlyStats['completed']++;
            }
            $assignDt = $bk->currentStoreAssignment?->assigned_at ?? $bk->currentStoreAssignment?->created_at ?? $bk->created_at;
            if ($assignDt) {
                $dayKey = $assignDt->copy()->timezone($period['tz'])->format('d M');
                $dailyChart[$dayKey] = ($dailyChart[$dayKey] ?? 0) + 1;
            }
        }

        // Monthly Confirmed Handovers (filtered by accepted_at / requested_at)
        $monthlyHandoversCount = BookingStoreHandover::query()
            ->where('store_id', $store->id)
            ->where('status', BookingStoreHandover::STATUS_ACCEPTED)
            ->whereRaw('COALESCE(accepted_at, requested_at, created_at) >= ? AND COALESCE(accepted_at, requested_at, created_at) < ?', [$startStr, $endStr])
            ->count();

        // Monthly Eligible Store Share (by earning creation/finalization date, multi-currency safe)
        $monthlyEarningsByCurrency = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->where('created_at', '>=', $startStr)
            ->where('created_at', '<', $endStr)
            ->whereHas('booking', fn ($q) => $q->where('booking_status', '!=', Booking::STATUS_CANCELLED))
            ->get()
            ->groupBy(fn ($e) => strtoupper((string) ($e->currency ?: 'GBP')))
            ->map(fn ($rows, $cur) => [
                'currency' => $cur,
                'net_minor' => (int) $rows->sum('net_store_earnings_minor'),
            ])
            ->values()
            ->all();

        // Current Pending Actions (always live across all months)
        $pendingHandoversCount = BookingStoreHandover::query()
            ->where('store_id', $store->id)
            ->where('status', BookingStoreHandover::STATUS_PENDING)
            ->count();

        $activeProcessingCount = $this->storeBookingsBaseQuery($store->id)
            ->whereIn('booking_status', [
                Booking::STATUS_STORE_ASSIGNED,
                Booking::STATUS_DELIVERY_MAN_ASSIGNED,
                Booking::STATUS_FINALIZED,
                Booking::STATUS_DELIVERY_MAN_PICKED_UP,
                Booking::STATUS_STORE_RECEIVED,
                Booking::STATUS_STORE_PROCESSING,
            ])
            ->count();

        $recentBookings = $monthlyAssigned->sortByDesc(fn ($b) => $b->currentStoreAssignment?->assigned_at?->timestamp ?? $b->created_at?->timestamp ?? 0)->take(8)->values();
        $recentHandovers = BookingStoreHandover::query()
            ->with(['booking:id,reference,customer_name', 'collectionDeliveryPerson:id,full_name'])
            ->where('store_id', $store->id)
            ->whereRaw('COALESCE(accepted_at, requested_at, created_at) >= ? AND COALESCE(accepted_at, requested_at, created_at) < ?', [$startStr, $endStr])
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $accountSummary = $this->accountService->getStoreAccountSummary($store);

        $viewData = array_merge($period, [
            'storeUser' => $storeUser,
            'store' => $store,
            'monthlyStats' => $monthlyStats,
            'monthlyHandoversCount' => $monthlyHandoversCount,
            'monthlyEarningsByCurrency' => $monthlyEarningsByCurrency,
            'dailyChart' => $dailyChart,
            'pendingHandoversCount' => $pendingHandoversCount,
            'activeProcessingCount' => $activeProcessingCount,
            'accountSummary' => $accountSummary,
            'recentBookings' => $recentBookings,
            'recentHandovers' => $recentHandovers,
            'currencyService' => $this->currencyService,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'month' => $period['selectedMonth'],
                'year' => $period['selectedYear'],
                'month_label' => $period['monthLabel'],
                'prev_month' => $period['prevMonth'],
                'prev_year' => $period['prevYear'],
                'next_month' => $period['nextMonth'],
                'next_year' => $period['nextYear'],
                'results_html' => view('store-portal.partials.dashboard-content', $viewData)->render(),
            ]);
        }

        return view('store-portal.dashboard', $viewData);
    }

    public function bookings(Request $request): View|JsonResponse
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $period = $this->resolveMonthPeriod($request);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'string', 'in:all,new,in_progress,completed,cancelled'],
            'store_stage' => ['nullable', 'string', 'in:all,awaiting_receive,store_received,store_processing,store_complete'],
            'pricing' => ['nullable', 'string', 'in:all,finalized,not_finalized'],
            'sort' => ['nullable', 'string', 'in:assigned_desc,assigned_asc,updated_desc,scheduled_desc'],
            'active_only' => ['nullable', 'boolean'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $statusFilter = (string) ($validated['status'] ?? 'all');
        $storeStage = (string) ($validated['store_stage'] ?? 'all');
        $pricingFilter = (string) ($validated['pricing'] ?? 'all');
        $sort = (string) ($validated['sort'] ?? 'assigned_desc');
        $activeOnly = $request->boolean('active_only');

        $query = $this->storeBookingsBaseQuery($store->id)
            ->with([
                'currentStoreAssignment',
                'currentStoreEarning',
                'operationalItems',
            ]);

        if (! $activeOnly) {
            $startStr = $period['monthStartUtc'];
            $endStr = $period['nextMonthStartUtc'];
            $query->whereHas('currentStoreAssignment', function ($q) use ($startStr, $endStr) {
                $q->whereRaw('COALESCE(assigned_at, created_at) >= ? AND COALESCE(assigned_at, created_at) < ?', [$startStr, $endStr]);
            });
        } else {
            $query->whereNotIn('booking_status', [Booking::STATUS_COMPLETED, Booking::STATUS_DELIVERED, Booking::STATUS_CANCELLED]);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('address_snapshot', 'like', "%{$search}%");
            });
        }

        if ($statusFilter !== 'all') {
            match ($statusFilter) {
                'cancelled' => $query->where('booking_status', Booking::STATUS_CANCELLED),
                'completed' => $query->where(function ($q) {
                    $q->whereIn('booking_status', [Booking::STATUS_COMPLETED, Booking::STATUS_DELIVERED])
                        ->orWhere('completed_operational_stages', 'like', '%"' . Booking::STATUS_COMPLETED . '"%');
                }),
                'new' => $query
                    ->whereIn('booking_status', [Booking::STATUS_CONFIRMED, Booking::STATUS_PROCESSING, Booking::STATUS_STORE_ASSIGNED])
                    ->where(function ($q) {
                        $q->whereNull('completed_operational_stages')
                            ->orWhere('completed_operational_stages', 'not like', '%"' . Booking::STATUS_STORE_RECEIVED . '"%');
                    }),
                'in_progress' => $query
                    ->whereNotIn('booking_status', [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED, Booking::STATUS_DELIVERED])
                    ->where(function ($q) {
                        $q->whereNull('completed_operational_stages')
                            ->orWhere('completed_operational_stages', 'not like', '%"' . Booking::STATUS_COMPLETED . '"%');
                    }),
                default => null,
            };
        }

        if ($storeStage !== 'all') {
            match ($storeStage) {
                'awaiting_receive' => $query->where(function ($q) {
                    $q->whereNull('completed_operational_stages')
                        ->orWhere('completed_operational_stages', 'not like', '%"' . Booking::STATUS_STORE_RECEIVED . '"%');
                }),
                'store_received' => $query->where('booking_status', Booking::STATUS_STORE_RECEIVED),
                'store_processing' => $query->where('booking_status', Booking::STATUS_STORE_PROCESSING),
                'store_complete' => $query->where(function ($q) {
                    $q->where('booking_status', Booking::STATUS_STORE_COMPLETE)
                        ->orWhere('completed_operational_stages', 'like', '%"' . Booking::STATUS_STORE_COMPLETE . '"%');
                }),
                default => null,
            };
        }

        if ($pricingFilter === 'finalized') {
            $query->whereHas('currentStoreEarning');
        } elseif ($pricingFilter === 'not_finalized') {
            $query->whereDoesntHave('currentStoreEarning');
        }

        match ($sort) {
            'assigned_asc' => $query->orderBy('created_at')->orderBy('id'),
            'updated_desc' => $query->orderByDesc('updated_at')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $allMatched = (clone $query)->get();
        $summary = [
            'total' => $allMatched->count(),
            'waiting_receive' => 0,
            'processing' => 0,
            'store_completed' => 0,
            'pending_handover' => 0,
        ];
        foreach ($allMatched as $bk) {
            $stages = is_array($bk->completed_operational_stages) ? $bk->completed_operational_stages : [];
            if (! in_array(Booking::STATUS_STORE_RECEIVED, $stages, true)) {
                $summary['waiting_receive']++;
                $summary['pending_handover']++;
            } elseif ($bk->booking_status === Booking::STATUS_STORE_PROCESSING || $bk->booking_status === Booking::STATUS_STORE_RECEIVED) {
                $summary['processing']++;
            }
            if (in_array(Booking::STATUS_STORE_COMPLETE, $stages, true) || $bk->booking_status === Booking::STATUS_STORE_COMPLETE) {
                $summary['store_completed']++;
            }
        }

        $bookings = $query->paginate(10)->withQueryString();
        $statusService = app(StoreOperationalStatusService::class);
        $storeStatusMap = $bookings->getCollection()->mapWithKeys(
            fn (Booking $bk) => [$bk->id => $statusService->getStatusForBooking($bk)]
        );

        $viewData = array_merge($period, [
            'storeUser' => $storeUser,
            'store' => $store,
            'bookings' => $bookings,
            'storeStatusMap' => $storeStatusMap,
            'currencyService' => $this->currencyService,
            'summary' => $summary,
            'filters' => [
                'q' => $search,
                'status' => $statusFilter,
                'store_stage' => $storeStage,
                'pricing' => $pricingFilter,
                'sort' => $sort,
                'active_only' => $activeOnly,
            ],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'month' => $period['selectedMonth'],
                'year' => $period['selectedYear'],
                'month_label' => $period['monthLabel'],
                'prev_month' => $period['prevMonth'],
                'prev_year' => $period['prevYear'],
                'next_month' => $period['nextMonth'],
                'next_year' => $period['nextYear'],
                'summary' => $summary,
                'results_html' => view('store-portal.bookings.partials.table', $viewData)->render(),
            ]);
        }

        return view('store-portal.bookings.index', $viewData);
    }

    public function showBooking(Booking $booking): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;

        $booking->load([
            'currentStoreAssignment.store',
            'operationalItems.category',
            'operationalItems.item',
            'currentStoreEarning',
            'currentFinalDeliveryAssignment.deliveryPerson',
            'latestStorePickupRequest.deliveryPerson',
            'storePickupRequests.deliveryPerson',
            'activeTagAssignments.tag',
            'activeTagAssignments.verifiedByStoreUser',
        ]);

        // Strict store ownership validation
        if (! $booking->currentStoreAssignment || (int) $booking->currentStoreAssignment->store_id !== (int) $store->id) {
            abort(403, 'You are not authorized to access this booking.');
        }

        $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);
        $categoryGroups = $booking->getActiveOperationalCategoryGroups();
        $pricingSummary = $booking->getOperationalPricingSummary($this->currencyService, false);
        $paymentState = app(\App\Services\CollectionPaymentService::class)->getPaymentState($booking);

        // Store earnings snapshot is only created when store commission rate is configured
        $storeEarning = $this->earningsService->getOrCreateSnapshotForBooking($booking);
        $isFinanciallyVisible = $storeEarning !== null;

        $storeStatus = app(StoreOperationalStatusService::class)->getStatusForBooking($booking);

        return view('store-portal.bookings.show', [
            'storeUser' => $storeUser,
            'store' => $store,
            'booking' => $booking,
            'categoryGroups' => $categoryGroups,
            'pricingSummary' => $pricingSummary,
            'paymentState' => $paymentState,
            'isFinanciallyVisible' => $isFinanciallyVisible,
            'storeEarning' => $storeEarning,
            'storeStatus' => $storeStatus,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function earnings(Request $request): View|JsonResponse
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $currency = $this->withdrawalService->getStorePrimaryCurrency($store);

        $month = $request->input('month');
        $search = trim((string) $request->input('q', ''));
        $wdStatus = $request->input('withdrawal_status');

        $summary = $this->withdrawalService->getMonthlySummary($store, $month, $currency);
        $activeMonth = $summary['period']['month'];

        $earnings = $this->withdrawalService->getMonthlyEarnings($store, $activeMonth, $search, $currency);
        $withdrawals = $this->withdrawalService->getMonthlyWithdrawals($store, $activeMonth, $wdStatus ?: null, $currency);
        $ledgerEntries = $this->withdrawalService->getMonthlyAccountActivity($store, $activeMonth, $currency);

        $bankAccounts = StoreBankAccount::query()
            ->where('store_id', $store->id)
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
                    'revenue_month' => $this->currencyService->formatMinor($summary['revenue_month_minor'], $currency),
                    'commission_month' => $this->currencyService->formatMinor($summary['commission_month_minor'], $currency),
                    'store_share_month' => $this->currencyService->formatMinor($summary['store_share_month_minor'], $currency),
                    'available_balance' => $this->currencyService->formatMinor($summary['available_balance_minor'], $currency),
                    'pending_withdrawal' => $this->currencyService->formatMinor($summary['pending_withdrawal_minor'], $currency),
                    'approved_month' => $this->currencyService->formatMinor($summary['approved_month_minor'], $currency),
                    'settled_month' => $this->currencyService->formatMinor($summary['settled_month_minor'], $currency),
                ],
                'bank_accounts' => $bankAccounts,
                'earnings_html' => view('store-portal.earnings.partials.earnings-table', [
                    'earnings' => $earnings,
                    'currencyService' => $this->currencyService,
                ])->render(),
                'withdrawals_html' => view('store-portal.earnings.partials.withdrawals-table', [
                    'withdrawals' => $withdrawals,
                    'currencyService' => $this->currencyService,
                ])->render(),
                'ledger_html' => view('store-portal.earnings.partials.ledger-table', [
                    'ledgerEntries' => $ledgerEntries,
                    'currencyService' => $this->currencyService,
                ])->render(),
            ]);
        }

        return view('store-portal.earnings.index', [
            'storeUser' => $storeUser,
            'store' => $store,
            'currency' => $currency,
            'summary' => $summary,
            'earnings' => $earnings,
            'withdrawals' => $withdrawals,
            'ledgerEntries' => $ledgerEntries,
            'bankAccounts' => $bankAccounts,
            'currencyService' => $this->currencyService,
            'activeWithdrawalStatus' => $wdStatus,
            'filters' => [
                'q' => $search,
            ],
        ]);
    }

    public function account(Request $request): View|JsonResponse
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $period = $this->resolveMonthPeriod($request);

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:all,booking_earning,payout,positive_adjustment,negative_adjustment,reversal'],
            'direction' => ['nullable', 'string', 'in:all,credit,debit'],
            'currency' => ['nullable', 'string', 'max:10'],
            'q' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'string', 'in:newest,oldest,amount_desc'],
        ]);

        $typeFilter = (string) ($validated['type'] ?? 'all');
        $directionFilter = (string) ($validated['direction'] ?? 'all');
        $currencyFilter = strtoupper(trim((string) ($validated['currency'] ?? 'all')));
        $search = trim((string) ($validated['q'] ?? ''));
        $sort = (string) ($validated['sort'] ?? 'newest');

        // Current authoritative balances (never filtered by month)
        $accountSummary = $this->accountService->getStoreAccountSummary($store);
        $currencies = array_keys($accountSummary);

        $startStr = $period['monthStartUtc'];
        $endStr = $period['nextMonthStartUtc'];

        // Selected-month financial activity
        $monthEarnings = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->where('created_at', '>=', $startStr)
            ->where('created_at', '<', $endStr)
            ->whereHas('booking', fn ($q) => $q->where('booking_status', '!=', Booking::STATUS_CANCELLED))
            ->get()
            ->groupBy(fn ($e) => strtoupper((string) ($e->currency ?: 'GBP')));

        $monthlyActivityByCurrency = [];
        foreach ($monthEarnings as $cur => $rows) {
            $monthlyActivityByCurrency[$cur] = [
                'currency' => $cur,
                'store_share_minor' => (int) $rows->sum('net_store_earnings_minor'),
                'commission_minor' => (int) $rows->sum('commission_amount_minor'),
                'payout_minor' => 0,
            ];
        }

        $monthPayouts = StoreTransaction::query()
            ->where('store_id', $store->id)
            ->where('transaction_type', StoreTransaction::TYPE_PAYOUT)
            ->where('created_at', '>=', $startStr)
            ->where('created_at', '<', $endStr)
            ->get()
            ->groupBy(fn ($t) => strtoupper((string) ($t->currency ?: 'GBP')));

        foreach ($monthPayouts as $cur => $rows) {
            if (! isset($monthlyActivityByCurrency[$cur])) {
                $monthlyActivityByCurrency[$cur] = ['currency' => $cur, 'store_share_minor' => 0, 'commission_minor' => 0, 'payout_minor' => 0];
            }
            $monthlyActivityByCurrency[$cur]['payout_minor'] = (int) $rows->sum(fn ($t) => StoreAccountService::decimalToMinor($t->amount));
        }

        $txQuery = StoreTransaction::query()
            ->where('store_id', $store->id)
            ->with('booking:id,reference')
            ->where('created_at', '>=', $startStr)
            ->where('created_at', '<', $endStr);

        if ($typeFilter !== 'all') {
            $txQuery->where('transaction_type', $typeFilter);
        }
        if ($directionFilter !== 'all') {
            $txQuery->where('direction', $directionFilter);
        }
        if ($currencyFilter !== 'ALL' && $currencyFilter !== '') {
            $txQuery->where('currency', $currencyFilter);
        }
        if ($search !== '') {
            $txQuery->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$search}%"));
            });
        }

        match ($sort) {
            'oldest' => $txQuery->orderBy('created_at')->orderBy('id'),
            'amount_desc' => $txQuery->orderByDesc('amount')->orderByDesc('id'),
            default => $txQuery->orderByDesc('created_at')->orderByDesc('id'),
        };

        $transactions = $txQuery->paginate(15)->withQueryString();

        $postedEarningIds = StoreTransaction::where('store_id', $store->id)
            ->where('transaction_type', StoreTransaction::TYPE_BOOKING_EARNING)
            ->whereNotNull('booking_store_earning_id')
            ->pluck('booking_store_earning_id')
            ->all();

        $pendingItems = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->when($postedEarningIds !== [], fn ($q) => $q->whereNotIn('id', $postedEarningIds))
            ->whereHas('booking', fn ($q) => $q->where('booking_status', '!=', Booking::STATUS_CANCELLED))
            ->with('booking:id,reference,booking_status')
            ->orderByDesc('finalized_at')
            ->limit(10)
            ->get();

        $viewData = array_merge($period, [
            'storeUser' => $storeUser,
            'store' => $store,
            'accountSummary' => $accountSummary,
            'monthlyActivityByCurrency' => array_values($monthlyActivityByCurrency),
            'currencies' => $currencies,
            'transactions' => $transactions,
            'pendingItems' => $pendingItems,
            'currencyService' => $this->currencyService,
            'filters' => [
                'type' => $typeFilter,
                'direction' => $directionFilter,
                'currency' => $currencyFilter === 'ALL' ? 'all' : $currencyFilter,
                'q' => $search,
                'sort' => $sort,
            ],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'month' => $period['selectedMonth'],
                'year' => $period['selectedYear'],
                'month_label' => $period['monthLabel'],
                'prev_month' => $period['prevMonth'],
                'prev_year' => $period['prevYear'],
                'next_month' => $period['nextMonth'],
                'next_year' => $period['nextYear'],
                'monthly_html' => view('store-portal.account.partials.monthly-activity', $viewData)->render(),
                'results_html' => view('store-portal.account.partials.transactions-table', $viewData)->render(),
            ]);
        }

        return view('store-portal.account.index', $viewData);
    }

    public function profile(): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $bankAccounts = StoreBankAccount::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return view('store-portal.profile', [
            'storeUser' => $storeUser,
            'store' => $store,
            'storeCurrency' => $this->withdrawalService->getStorePrimaryCurrency($store),
            'bankAccounts' => $bankAccounts,
            'countriesMeta' => $this->bankService->getCountriesMeta(),
            'currencies' => \App\Support\CurrencyCatalog::CURRENCIES,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse|JsonResponse
    {
        $storeUser = $this->storeUser();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'phone_country_code' => ['nullable', 'string', 'max:12'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $normalized = StoreUser::normalizePhone($validated['phone'] ?? null, $validated['phone_country_code'] ?? null);
        if ($normalized !== null) {
            $conflict = StoreUser::where('phone_normalized', $normalized)
                ->where('id', '!=', $storeUser->id)
                ->exists();
            if ($conflict) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => [
                            'phone' => ['This phone number is already associated with another store account.'],
                        ],
                    ], 422);
                }

                return back()->withErrors(['phone' => 'This phone number is already associated with another store account.'])->withInput();
            }
        }

        $storeUser->update([
            'name' => trim($validated['name']),
            'phone_country_code' => $validated['phone_country_code'] ?: null,
            'phone' => $validated['phone'] ? trim($validated['phone']) : null,
            'phone_normalized' => $normalized,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Your store account profile has been updated.',
                'store_user' => [
                    'name' => $storeUser->name,
                    'phone_country_code' => $storeUser->phone_country_code,
                    'phone' => $storeUser->phone,
                ],
            ]);
        }

        return back()->with('success', 'Your store account profile has been updated.');
    }

    public function showChangePassword(): View
    {
        $storeUser = $this->storeUser();

        return view('store-portal.change-password', [
            'storeUser' => $storeUser,
            'store' => $storeUser->store,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse|JsonResponse
    {
        $storeUser = $this->storeUser();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'password.confirmed' => 'New password confirmation does not match.',
        ]);

        if (! Hash::check($validated['current_password'], $storeUser->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => [
                        'current_password' => ['Current password is incorrect.'],
                    ],
                ], 422);
            }

            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        if (Hash::check($validated['password'], $storeUser->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => [
                        'password' => ['New password must be different from your current password.'],
                    ],
                ], 422);
            }

            return back()->withErrors(['password' => 'New password must be different from your current password.']);
        }

        $storeUser->update([
            'password' => $validated['password'],
        ]);

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Password updated successfully.',
            ]);
        }

        return back()->with('success', 'Password updated successfully.');
    }

    public function handovers(Request $request): View|JsonResponse
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $period = $this->resolveMonthPeriod($request);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:all,pending,accepted,rejected'],
            'q' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'string', 'in:pending_first,newest,oldest'],
            'pending_only' => ['nullable', 'boolean'],
        ]);

        $statusFilter = (string) ($validated['status'] ?? 'all');
        $search = trim((string) ($validated['q'] ?? ''));
        $sort = (string) ($validated['sort'] ?? 'pending_first');
        $pendingOnly = $request->boolean('pending_only');

        $allTimePendingCount = BookingStoreHandover::query()
            ->where('store_id', $store->id)
            ->where('status', BookingStoreHandover::STATUS_PENDING)
            ->count();

        $query = BookingStoreHandover::query()
            ->with([
                'booking.operationalItems',
                'booking.activeTagAssignments.tag',
                'booking.activeTagAssignments.verifiedByStoreUser',
                'collectionDeliveryPerson',
                'acceptedByStoreUser',
                'rejectedByStoreUser',
            ])
            ->where('store_id', $store->id);

        if ($pendingOnly) {
            $query->where('status', BookingStoreHandover::STATUS_PENDING);
        } else {
            $query->whereRaw('COALESCE(accepted_at, requested_at, created_at) >= ? AND COALESCE(accepted_at, requested_at, created_at) < ?', [
                $period['monthStartUtc'],
                $period['nextMonthStartUtc'],
            ]);
            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('request_reference', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%"))
                    ->orWhereHas('collectionDeliveryPerson', fn ($dq) => $dq->where('full_name', 'like', "%{$search}%"));
            });
        }

        $allMatched = (clone $query)->get();
        $summary = [
            'total' => $allMatched->count(),
            'pending' => $allMatched->where('status', BookingStoreHandover::STATUS_PENDING)->count(),
            'accepted' => $allMatched->where('status', BookingStoreHandover::STATUS_ACCEPTED)->count(),
            'rejected' => $allMatched->where('status', BookingStoreHandover::STATUS_REJECTED)->count(),
        ];

        match ($sort) {
            'newest' => $query->orderByDesc('requested_at')->orderByDesc('id'),
            'oldest' => $query->orderBy('requested_at')->orderBy('id'),
            default => $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->orderByDesc('requested_at'),
        };

        $handovers = $query->paginate(12)->withQueryString();

        $viewData = array_merge($period, [
            'storeUser' => $storeUser,
            'store' => $store,
            'handovers' => $handovers,
            'summary' => $summary,
            'allTimePendingCount' => $allTimePendingCount,
            'filters' => [
                'status' => $statusFilter,
                'q' => $search,
                'sort' => $sort,
                'pending_only' => $pendingOnly,
            ],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'month' => $period['selectedMonth'],
                'year' => $period['selectedYear'],
                'month_label' => $period['monthLabel'],
                'prev_month' => $period['prevMonth'],
                'prev_year' => $period['prevYear'],
                'next_month' => $period['nextMonth'],
                'next_year' => $period['nextYear'],
                'summary' => $summary,
                'results_html' => view('store-portal.handovers.partials.list', $viewData)->render(),
            ]);
        }

        return view('store-portal.handovers.index', $viewData);
    }

    public function acceptHandover(Request $request, BookingStoreHandover $handover): RedirectResponse|JsonResponse
    {
        $storeUser = $this->storeUser();

        if ((int) $handover->store_id !== (int) $storeUser->store_id) {
            abort(403, 'Unauthorized store handover request.');
        }

        try {
            $accepted = app(StoreHandoverService::class)->acceptHandover($handover, $storeUser);
        } catch (\RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withErrors(['handover' => $e->getMessage()]);
        }

        $msg = "Handover {$accepted->request_reference} accepted. Booking workflow Store Received is now Done.";

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $msg,
                'handover_id' => $accepted->id,
                'status' => $accepted->status,
                'accepted_at' => $accepted->accepted_at?->format('d M Y, H:i'),
                'accepted_by' => $accepted->acceptedByStoreUser?->name,
            ])
            : back()->with('success', $msg);
    }

    public function verifyBookingTag(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $storeUser = $this->storeUser();

        $validated = $request->validate([
            'tag_code' => ['required', 'string', 'max:120'],
            'method' => ['nullable', 'in:manual,qr'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $verified = app(\App\Services\BookingTagAssignmentService::class)->verifyTagForStoreHandover(
                $booking,
                $storeUser,
                $validated['tag_code'],
                $validated['method'] ?? null,
                $validated['notes'] ?? null
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Tag verification failed.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $booking->load(['activeTagAssignments.tag', 'activeTagAssignments.verifiedByStoreUser']);
        $msg = "Tag {$verified->tag->tag_code} verified at store by {$storeUser->name}. Click Accept & Confirm Received to finish handover receipt.";

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $msg,
                'tags_html' => view('store-portal.bookings.partials.tag-verification-list', compact('booking'))->render(),
            ])
            : back()->with('success', $msg);
    }

    public function rejectHandover(Request $request, BookingStoreHandover $handover): RedirectResponse|JsonResponse
    {
        $storeUser = $this->storeUser();

        if ((int) $handover->store_id !== (int) $storeUser->store_id) {
            abort(403, 'Unauthorized store handover request.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $rejected = app(StoreHandoverService::class)->rejectHandover($handover, $storeUser, $validated['reason']);
        } catch (\RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withErrors(['reason' => $e->getMessage()]);
        }

        $msg = "Issue reported for handover {$rejected->request_reference}.";

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $msg,
                'handover_id' => $rejected->id,
                'status' => $rejected->status,
            ])
            : back()->with('success', $msg);
    }

    public function markStoreComplete(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $storeUser = $this->storeUser();
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $updated = app(\App\Services\StorePickupDeliveryService::class)->markStoreComplete(
                $booking,
                $storeUser,
                $validated['note'] ?? null,
                $request->ip()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to mark Store Complete.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors());
        }

        $storeStatus = app(StoreOperationalStatusService::class)->getStatusForBooking($updated);
        $msg = "Booking {$updated->reference} marked Store Complete.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'booking_status' => $updated->booking_status,
                'booking_status_label' => $updated->getStatusLabel(),
                'store_status' => $storeStatus,
                'has_final_assignment' => $updated->hasValidFinalDeliveryCourierAssignment(),
            ]);
        }

        return back()->with('success', $msg);
    }

    public function requestStorePickup(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $storeUser = $this->storeUser();
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $pickupRequest = app(\App\Services\StorePickupDeliveryService::class)->requestStorePickup(
                $booking,
                $storeUser,
                $validated['note'] ?? null,
                $request->ip()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to send store pickup request.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors());
        }

        $msg = "Store pickup request {$pickupRequest->request_reference} sent to {$pickupRequest->deliveryPerson?->full_name}.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'pickup_request' => [
                    'id' => $pickupRequest->id,
                    'request_reference' => $pickupRequest->request_reference,
                    'status' => $pickupRequest->status,
                    'status_label' => $pickupRequest->getStatusLabel(),
                    'requested_at' => $pickupRequest->requested_at?->format('d M Y, H:i'),
                    'delivery_person_name' => $pickupRequest->deliveryPerson?->full_name,
                    'delivery_person_phone' => $pickupRequest->deliveryPerson?->phone_display ?: 'Not provided',
                ],
            ]);
        }

        return back()->with('success', $msg);
    }
}
