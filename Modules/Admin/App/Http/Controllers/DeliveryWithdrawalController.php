<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPersonWithdrawalRequest;
use App\Services\CurrencyService;
use App\Services\DeliveryPersonCashService;
use App\Services\DeliveryPersonWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;

class DeliveryWithdrawalController extends Controller
{
    public function __construct(
        protected DeliveryPersonWithdrawalService $withdrawalService,
        protected DeliveryPersonCashService $cashService,
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $status = $request->input('status');
        $dpId = $request->input('delivery_person_id');
        $rawMonth = $request->input('month');
        $showAllMonths = $rawMonth === 'all';
        $period = $this->withdrawalService->resolveMonthPeriod($showAllMonths ? null : $rawMonth);
        $activeMonth = $showAllMonths ? 'all' : $period['month'];
        $search = trim((string) $request->input('search', ''));

        $baseQuery = DeliveryPersonWithdrawalRequest::query();
        if (! $showAllMonths) {
            $baseQuery->where('request_month', $period['month']);
        }
        if ($dpId) {
            $baseQuery->where('delivery_person_id', (int) $dpId);
        }

        $query = (clone $baseQuery)
            ->with(['deliveryPerson:id,code,full_name,currency', 'approvedByAdmin:id,name', 'rejectedByAdmin:id,name', 'settledByAdmin:id,name'])
            ->orderByDesc('id');

        if ($status && in_array($status, [
            DeliveryPersonWithdrawalRequest::STATUS_PENDING,
            DeliveryPersonWithdrawalRequest::STATUS_APPROVED,
            DeliveryPersonWithdrawalRequest::STATUS_REJECTED,
            DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED,
            DeliveryPersonWithdrawalRequest::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('withdrawal_reference', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%")
                    ->orWhereHas('deliveryPerson', fn ($dq) => $dq->where('full_name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $withdrawals = $query->paginate(20)->withQueryString();

        $pendingCount = (clone $baseQuery)->where('status', DeliveryPersonWithdrawalRequest::STATUS_PENDING)->count();
        $approvedCount = (clone $baseQuery)->where('status', DeliveryPersonWithdrawalRequest::STATUS_APPROVED)->count();
        $settledCount = (clone $baseQuery)->where('status', DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED)->count();
        $rejectedCount = (clone $baseQuery)->where('status', DeliveryPersonWithdrawalRequest::STATUS_REJECTED)->count();

        $defaultCur = $this->currencyService->code();
        $pendingAmount = (int) (clone $baseQuery)->where('status', DeliveryPersonWithdrawalRequest::STATUS_PENDING)->sum('amount_minor');
        $approvedAmount = (int) (clone $baseQuery)->where('status', DeliveryPersonWithdrawalRequest::STATUS_APPROVED)->sum('amount_minor');
        $settledAmount = (int) (clone $baseQuery)->where('status', DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED)->sum('amount_minor');
        $rejectedAmount = (int) (clone $baseQuery)->where('status', DeliveryPersonWithdrawalRequest::STATUS_REJECTED)->sum('amount_minor');

        $kpis = [
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'settled' => $settledCount,
            'rejected' => $rejectedCount,
            'pending_formatted' => $this->currencyService->formatMinor($pendingAmount, $defaultCur),
            'approved_formatted' => $this->currencyService->formatMinor($approvedAmount, $defaultCur),
            'settled_formatted' => $this->currencyService->formatMinor($settledAmount, $defaultCur),
            'rejected_formatted' => $this->currencyService->formatMinor($rejectedAmount, $defaultCur),
        ];

        $persons = DeliveryPerson::withTrashed()->orderBy('full_name')->get(['id', 'code', 'full_name']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'period' => $period,
                'active_month' => $activeMonth,
                'counts' => $kpis,
                'table_html' => view('admin::delivery-persons.partials.withdrawals-table', [
                    'withdrawals' => $withdrawals,
                    'currencyService' => $this->currencyService,
                ])->render(),
            ]);
        }

        return view('admin::delivery-persons.withdrawals', [
            'withdrawals' => $withdrawals,
            'persons' => $persons,
            'currencyService' => $this->currencyService,
            'period' => $period,
            'activeMonth' => $activeMonth,
            'kpis' => $kpis,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'settledCount' => $settledCount,
            'rejectedCount' => $rejectedCount,
            'activeStatus' => $status,
            'activeDpId' => $dpId,
            'search' => $search,
        ]);
    }

    public function show(DeliveryPersonWithdrawalRequest $withdrawal): JsonResponse
    {
        $withdrawal->load(['deliveryPerson', 'approvedByAdmin:id,name', 'rejectedByAdmin:id,name', 'settledByAdmin:id,name']);
        $person = $withdrawal->deliveryPerson;
        $cur = $withdrawal->currency;
        $currentAvailMinor = $person ? $this->cashService->getEarningsBalanceMinor($person, $cur) : 0;

        $recentEntries = $person
            ? DeliveryPersonLedgerEntry::query()
                ->where('delivery_person_id', $person->id)
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->map(fn (DeliveryPersonLedgerEntry $e) => [
                    'reference' => $e->entry_reference,
                    'type' => ucfirst(str_replace('_', ' ', (string) $e->transaction_type)),
                    'direction' => $e->direction,
                    'amount_formatted' => $this->currencyService->formatMinor((int) $e->amount_minor, $e->currency),
                    'occurred_at' => ($e->occurred_at ?? $e->created_at)?->format('d M Y, H:i'),
                ])->values()
            : [];

        return response()->json([
            'status' => 'ok',
            'withdrawal' => [
                'id' => $withdrawal->id,
                'withdrawal_reference' => $withdrawal->withdrawal_reference,
                'delivery_person_name' => $person?->full_name ?? 'Delivery Person',
                'delivery_person_code' => $person?->code ?? '-',
                'amount_formatted' => $this->currencyService->formatMinor($withdrawal->amount_minor, $cur),
                'available_before_formatted' => $this->currencyService->formatMinor($withdrawal->available_balance_before_minor, $cur),
                'current_available_formatted' => $this->currencyService->formatMinor($currentAvailMinor, $cur),
                'currency' => $cur,
                'request_month' => $withdrawal->request_month,
                'status' => $withdrawal->status,
                'status_meta' => $withdrawal->statusMeta(),
                'bank_snapshot' => $withdrawal->bank_snapshot_json,
                'notes' => $withdrawal->notes,
                'rejection_reason' => $withdrawal->rejection_reason,
                'payment_date' => $withdrawal->payment_date?->format('d M Y'),
                'external_reference' => $withdrawal->external_reference,
                'settlement_notes' => $withdrawal->settlement_notes,
                'requested_at' => $withdrawal->requested_at?->format('d M Y, H:i'),
                'approved_at' => $withdrawal->approved_at?->format('d M Y, H:i'),
                'approved_by' => $withdrawal->approvedByAdmin?->name,
                'rejected_at' => $withdrawal->rejected_at?->format('d M Y, H:i'),
                'rejected_by' => $withdrawal->rejectedByAdmin?->name,
                'settled_at' => $withdrawal->settled_at?->format('d M Y, H:i'),
                'settled_by' => $withdrawal->settledByAdmin?->name,
                'audit_history' => $withdrawal->audit_history ?? [],
                'recent_ledger' => $recentEntries,
            ],
        ]);
    }

    public function approve(Request $request, DeliveryPersonWithdrawalRequest $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'payout_method' => ['nullable', 'in:bank_transfer,cash,other'],
            'paid_from_account' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->withdrawalService->approveWithdrawal(
            $withdrawal,
            (int) Auth::guard('admin')->id(),
            $validated['note'] ?? null,
            $validated['payout_method'] ?? 'bank_transfer',
            $validated['paid_from_account'] ?? null
        );

        return response()->json([
            'status' => 'ok',
            'message' => "Withdrawal {$updated->withdrawal_reference} approved. Reserved funds remain committed for manual transfer.",
            'withdrawal_status' => $updated->status,
        ]);
    }

    public function reject(Request $request, DeliveryPersonWithdrawalRequest $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $updated = $this->withdrawalService->rejectWithdrawal(
            $withdrawal,
            (int) Auth::guard('admin')->id(),
            $validated['reason']
        );

        return response()->json([
            'status' => 'ok',
            'message' => "Withdrawal {$updated->withdrawal_reference} rejected and reserved balance restored.",
            'withdrawal_status' => $updated->status,
        ]);
    }

    public function settle(Request $request, DeliveryPersonWithdrawalRequest $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'settlement_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->withdrawalService->markManuallySettled(
            $withdrawal,
            (int) Auth::guard('admin')->id(),
            $validated
        );

        return response()->json([
            'status' => 'ok',
            'message' => "Withdrawal {$updated->withdrawal_reference} marked as Manually Settled.",
            'withdrawal_status' => $updated->status,
        ]);
    }
}
