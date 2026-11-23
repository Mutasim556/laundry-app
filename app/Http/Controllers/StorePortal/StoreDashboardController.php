<?php

namespace App\Http\Controllers\StorePortal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStoreEarning;
use App\Models\BookingStoreHandover;
use App\Models\StoreTransaction;
use App\Models\StoreUser;
use App\Services\CurrencyService;
use App\Services\StoreAccountService;
use App\Services\StoreEarningsService;
use App\Services\StoreHandoverService;
use App\Services\StoreOperationalStatusService;
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
        protected StoreAccountService $accountService
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

    public function dashboard(): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;

        $allAssigned = $this->storeBookingsBaseQuery($store->id)
            ->select(['id', 'booking_status', 'completed_operational_stages'])
            ->get();

        $stats = [
            'total' => $allAssigned->count(),
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];

        foreach ($allAssigned as $row) {
            $group = $row->getCustomerStatusGroup();
            if ($group === Booking::CUSTOMER_STATUS_PENDING) {
                $stats['pending']++;
            } elseif ($group === Booking::CUSTOMER_STATUS_IN_PROGRESS) {
                $stats['in_progress']++;
            } elseif ($group === Booking::CUSTOMER_STATUS_COMPLETED) {
                $stats['completed']++;
            }
        }

        $accountSummary = $this->accountService->getStoreAccountSummary($store);

        $recentBookings = $this->storeBookingsBaseQuery($store->id)
            ->with([
                'currentStoreAssignment',
                'currentStoreEarning',
                'operationalItems',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $pendingHandoversCount = BookingStoreHandover::query()
            ->where('store_id', $store->id)
            ->where('status', BookingStoreHandover::STATUS_PENDING)
            ->count();

        return view('store-portal.dashboard', [
            'storeUser' => $storeUser,
            'store' => $store,
            'stats' => $stats,
            'pendingHandoversCount' => $pendingHandoversCount,
            'accountSummary' => $accountSummary,
            'recentBookings' => $recentBookings,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function bookings(Request $request): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'string', 'in:all,new,in_progress,completed,cancelled'],
            'pricing' => ['nullable', 'string', 'in:all,finalized,not_finalized'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;
        $statusFilter = (string) ($validated['status'] ?? 'all');
        $pricingFilter = (string) ($validated['pricing'] ?? 'all');
        $tz = (string) config('app.timezone', 'UTC');

        $query = $this->storeBookingsBaseQuery($store->id)
            ->with([
                'currentStoreAssignment',
                'currentStoreEarning',
                'operationalItems',
            ]);

        if ($search !== '') {
            $query->where('reference', 'like', "%{$search}%");
        }
        if ($fromDate) {
            $query->where('created_at', '>=', Carbon::parse($fromDate, $tz)->startOfDay()->utc());
        }
        if ($toDate) {
            $query->where('created_at', '<=', Carbon::parse($toDate, $tz)->endOfDay()->utc());
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

        if ($pricingFilter === 'finalized') {
            $query->whereHas('currentStoreEarning');
        } elseif ($pricingFilter === 'not_finalized') {
            $query->whereDoesntHave('currentStoreEarning');
        }

        $bookings = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(10)->withQueryString();
        $statusService = app(StoreOperationalStatusService::class);
        $storeStatusMap = $bookings->getCollection()->mapWithKeys(
            fn (Booking $bk) => [$bk->id => $statusService->getStatusForBooking($bk)]
        );

        return view('store-portal.bookings.index', [
            'storeUser' => $storeUser,
            'store' => $store,
            'bookings' => $bookings,
            'storeStatusMap' => $storeStatusMap,
            'currencyService' => $this->currencyService,
            'filters' => [
                'q' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'status' => $statusFilter,
                'pricing' => $pricingFilter,
            ],
        ]);
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
        ]);

        // Strict store ownership validation
        if (! $booking->currentStoreAssignment || (int) $booking->currentStoreAssignment->store_id !== (int) $store->id) {
            abort(403, 'You are not authorized to access this booking.');
        }

        $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);
        $categoryGroups = $booking->getActiveOperationalCategoryGroups();

        // Financial data is ONLY computed/returned when Finalized workflow stage is Done & pricing complete
        $storeEarning = $this->earningsService->getOrCreateSnapshotForBooking($booking);
        $isFinanciallyVisible = $storeEarning !== null;

        $storeStatus = app(StoreOperationalStatusService::class)->getStatusForBooking($booking);

        return view('store-portal.bookings.show', [
            'storeUser' => $storeUser,
            'store' => $store,
            'booking' => $booking,
            'categoryGroups' => $categoryGroups,
            'isFinanciallyVisible' => $isFinanciallyVisible,
            'storeEarning' => $storeEarning,
            'storeStatus' => $storeStatus,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function earnings(Request $request): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;
        $tz = (string) config('app.timezone', 'UTC');

        $base = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true);

        $totalEarningsByCurrency = (clone $base)
            ->selectRaw('currency, SUM(net_store_earnings_minor) as net_minor, SUM(eligible_service_amount_minor) as base_minor, SUM(commission_amount_minor) as comm_minor')
            ->groupBy('currency')
            ->get();

        $thisMonthStart = now($tz)->startOfMonth()->utc();
        $currentPeriodByCurrency = (clone $base)
            ->where('finalized_at', '>=', $thisMonthStart)
            ->selectRaw('currency, SUM(net_store_earnings_minor) as net_minor')
            ->groupBy('currency')
            ->get();

        $completedEarningsByCurrency = (clone $base)
            ->whereHas('booking', fn ($q) => $q->where('booking_status', Booking::STATUS_COMPLETED))
            ->selectRaw('currency, SUM(net_store_earnings_minor) as net_minor')
            ->groupBy('currency')
            ->get();

        $query = (clone $base)->with('booking:id,reference,booking_status,currency');

        if ($search !== '') {
            $query->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$search}%"));
        }
        if ($fromDate) {
            $query->where('finalized_at', '>=', Carbon::parse($fromDate, $tz)->startOfDay()->utc());
        }
        if ($toDate) {
            $query->where('finalized_at', '<=', Carbon::parse($toDate, $tz)->endOfDay()->utc());
        }

        $earnings = $query->orderByDesc('finalized_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('store-portal.earnings.index', [
            'storeUser' => $storeUser,
            'store' => $store,
            'earnings' => $earnings,
            'totalEarningsByCurrency' => $totalEarningsByCurrency,
            'currentPeriodByCurrency' => $currentPeriodByCurrency,
            'completedEarningsByCurrency' => $completedEarningsByCurrency,
            'currencyService' => $this->currencyService,
            'filters' => [
                'q' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }

    public function account(Request $request): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;

        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'type' => ['nullable', 'string', 'in:all,booking_earning,payout,positive_adjustment,negative_adjustment,reversal'],
            'direction' => ['nullable', 'string', 'in:all,credit,debit'],
            'currency' => ['nullable', 'string', 'max:10'],
            'q' => ['nullable', 'string', 'max:60'],
        ]);

        $tz = (string) config('app.timezone', 'UTC');
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;
        $typeFilter = (string) ($validated['type'] ?? 'all');
        $directionFilter = (string) ($validated['direction'] ?? 'all');
        $currencyFilter = strtoupper(trim((string) ($validated['currency'] ?? 'all')));
        $search = trim((string) ($validated['q'] ?? ''));

        $accountSummary = $this->accountService->getStoreAccountSummary($store);
        $currencies = array_keys($accountSummary);

        $txQuery = StoreTransaction::query()
            ->where('store_id', $store->id)
            ->with('booking:id,reference');

        if ($fromDate) {
            $txQuery->where('created_at', '>=', Carbon::parse($fromDate, $tz)->startOfDay()->utc());
        }
        if ($toDate) {
            $txQuery->where('created_at', '<=', Carbon::parse($toDate, $tz)->endOfDay()->utc());
        }
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
                    ->orWhereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$search}%"));
            });
        }

        $transactions = $txQuery->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString();

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

        return view('store-portal.account.index', [
            'storeUser' => $storeUser,
            'store' => $store,
            'accountSummary' => $accountSummary,
            'currencies' => $currencies,
            'transactions' => $transactions,
            'pendingItems' => $pendingItems,
            'currencyService' => $this->currencyService,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'type' => $typeFilter,
                'direction' => $directionFilter,
                'currency' => $currencyFilter === 'ALL' ? 'all' : $currencyFilter,
                'q' => $search,
            ],
        ]);
    }

    public function profile(): View
    {
        $storeUser = $this->storeUser();

        return view('store-portal.profile', [
            'storeUser' => $storeUser,
            'store' => $storeUser->store,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
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
                return back()->withErrors(['phone' => 'This phone number is already associated with another store account.'])->withInput();
            }
        }

        $storeUser->update([
            'name' => trim($validated['name']),
            'phone_country_code' => $validated['phone_country_code'] ?: null,
            'phone' => $validated['phone'] ? trim($validated['phone']) : null,
            'phone_normalized' => $normalized,
        ]);

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

    public function updatePassword(Request $request): RedirectResponse
    {
        $storeUser = $this->storeUser();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $storeUser->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        $storeUser->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function handovers(): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;

        $handovers = BookingStoreHandover::query()
            ->with([
                'booking.operationalItems',
                'collectionDeliveryPerson',
                'acceptedByStoreUser',
                'rejectedByStoreUser',
            ])
            ->where('store_id', $store->id)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('requested_at')
            ->paginate(15);

        return view('store-portal.handovers.index', [
            'storeUser' => $storeUser,
            'store' => $store,
            'handovers' => $handovers,
        ]);
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
}
