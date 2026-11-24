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

    public function dashboard(): View
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));

        $activeBase = DeliveryAssignment::query()
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED]);

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

        $completedJobsCount = DeliveryAssignment::query()
            ->where('delivery_person_id', $person->id)
            ->whereIn('status', [DeliveryAssignment::STATUS_HANDED_OVER, DeliveryAssignment::STATUS_COMPLETED])
            ->count();

        $cashSummary = $this->cashService->getSummary($person, $currency);
        $earningsBalanceMinor = $this->cashService->getEarningsBalanceMinor($person, $currency);

        $recentAssignments = DeliveryAssignment::query()
            ->with(['booking', 'store', 'latestPickupRequest'])
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $pendingPickupRequests = \App\Models\BookingStorePickupRequest::query()
            ->with(['booking', 'store', 'deliveryAssignment'])
            ->where('delivery_person_id', $person->id)
            ->where('status', \App\Models\BookingStorePickupRequest::STATUS_PENDING)
            ->orderByDesc('requested_at')
            ->get();

        return view('delivery-person-portal.dashboard', [
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
        ]);
    }

    public function bookings(Request $request): View
    {
        return $this->listAssignments($request, null, 'My Assigned Bookings');
    }

    public function collections(Request $request): View
    {
        return $this->listAssignments($request, DeliveryAssignment::TYPE_CUSTOMER_COLLECTION, 'Collection Bookings');
    }

    public function finalDeliveries(Request $request): View
    {
        return $this->listAssignments($request, DeliveryAssignment::TYPE_FINAL_DELIVERY, 'Final Delivery Bookings');
    }

    protected function listAssignments(Request $request, ?string $type, string $heading): View
    {
        $person = $this->person();
        $jobTypeFilter = $type ?? ($request->filled('job_type') ? (string) $request->input('job_type') : null);
        $statusFilter = $request->filled('status_filter') ? (string) $request->input('status_filter') : null;

        $query = DeliveryAssignment::query()
            ->with(['booking.assignedStore', 'store', 'latestPickupRequest', 'ledgerEntries'])
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED])
            ->whereHas('booking')
            ->orderByDesc('id');

        if ($jobTypeFilter !== null && in_array($jobTypeFilter, [
            DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            DeliveryAssignment::TYPE_FINAL_DELIVERY,
        ], true)) {
            $query->where('assignment_type', $jobTypeFilter);
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $numericId = null;
            if (preg_match('/^(?:COL|DEL)-0*(\d+)$/i', $term, $m)) {
                $numericId = (int) $m[1];
            }

            $query->where(function ($q) use ($term, $numericId) {
                $q->where('assignment_reference', 'like', "%{$term}%")
                    ->orWhere('assignment_type', 'like', "%{$term}%")
                    ->orWhere('status', 'like', "%{$term}%")
                    ->orWhereHas('booking', function ($bq) use ($term) {
                        $bq->where('reference', 'like', "%{$term}%")
                            ->orWhere('customer_name', 'like', "%{$term}%")
                            ->orWhere('customer_phone', 'like', "%{$term}%");
                    })
                    ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));

                if ($numericId !== null) {
                    $q->orWhere('id', $numericId);
                }
            });
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $all = $query->get()->filter(function (DeliveryAssignment $asg) use ($statusFilter) {
                return $asg->getJobStatusDisplay()['key'] === $statusFilter;
            })->values();

            $perPage = 15;
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $assignments = new \Illuminate\Pagination\LengthAwarePaginator(
                $all->forPage($page, $perPage)->values(),
                $all->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $assignments = $query->paginate(15)->withQueryString();
        }

        return view('delivery-person-portal.bookings.index', [
            'person' => $person,
            'heading' => $heading,
            'filterType' => $type,
            'jobTypeFilter' => $jobTypeFilter,
            'statusFilter' => $statusFilter,
            'assignments' => $assignments,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function cashAccount(Request $request): View
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $cashSummary = $this->cashService->getSummary($person, $currency);

        $txQuery = DeliveryPersonCashTransaction::query()
            ->with(['booking', 'settlement'])
            ->where('delivery_person_id', $person->id)
            ->orderByDesc('id');

        if ($request->filled('from_date')) {
            $txQuery->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $txQuery->whereDate('created_at', '<=', $request->input('to_date'));
        }
        if ($request->filled('payment_channel')) {
            $txQuery->where('payment_channel', $request->input('payment_channel'));
        }
        if ($request->filled('transaction_type')) {
            $txQuery->where('transaction_type', $request->input('transaction_type'));
        }
        if ($request->filled('booking_reference')) {
            $ref = trim((string) $request->input('booking_reference'));
            $txQuery->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$ref}%"));
        }

        $transactions = $txQuery->paginate(20)->withQueryString();

        return view('delivery-person-portal.cash-account', [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'cashSummary' => $cashSummary,
            'transactions' => $transactions,
        ]);
    }

    public function settlements(): View
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $cashSummary = $this->cashService->getSummary($person, $currency);
        $settlements = DeliveryPersonSettlement::query()
            ->with('admin')
            ->where('delivery_person_id', $person->id)
            ->orderByDesc('settled_at')
            ->paginate(20);

        return view('delivery-person-portal.settlements', [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'cashSummary' => $cashSummary,
            'settlements' => $settlements,
        ]);
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
