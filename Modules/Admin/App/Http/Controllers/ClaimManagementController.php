<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BookingCustomerPayment;
use App\Models\CustomerServiceClaim;
use App\Models\RefundTransaction;
use App\Services\ClaimWorkflowService;
use App\Services\CurrencyService;
use App\Services\RefundCalculationService;
use App\Services\RefundExecutionService;
use App\Services\StoreAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Admin\Models\DeliveryPerson;

class ClaimManagementController extends Controller
{
    public function __construct(
        protected ClaimWorkflowService $claimService,
        protected RefundCalculationService $calcService,
        protected RefundExecutionService $refundService,
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request): View
    {
        $type = $request->input('type');
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));

        $query = CustomerServiceClaim::query()
            ->with(['booking:id,reference,customer_name,currency,payment_gateway', 'store:id,name', 'policyVersion:id,version_number'])
            ->orderByDesc('id');

        if ($type && in_array($type, [
            CustomerServiceClaim::TYPE_CANCELLATION_REFUND,
            CustomerServiceClaim::TYPE_REWASH,
            CustomerServiceClaim::TYPE_DAMAGE_MISSING,
        ], true)) {
            $query->where('claim_type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('case_reference', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%"));
            });
        }

        $claims = $query->paginate(20)->withQueryString();

        return view('admin::refund-returns.cases.index', [
            'claims' => $claims,
            'activeType' => $type,
            'activeStatus' => $status,
            'search' => $search,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function show(CustomerServiceClaim $claim): View
    {
        $claim->load([
            'booking.operationalItems',
            'booking.assignedStore',
            'booking.customerPayments' => fn ($q) => $q->orderByDesc('id'),
            'store',
            'policyVersion',
            'items.operationalItem',
            'evidence',
            'statusHistory',
            'refundTransactions',
            'deliveryJobs.deliveryPerson',
        ]);

        $refundPreview = $this->calcService->buildPreview($claim->booking, $claim);
        $deliveryPersons = DeliveryPerson::query()->where('work_status', 'active')->orderBy('full_name')->get(['id', 'full_name', 'code']);

        return view('admin::refund-returns.cases.show', [
            'claim' => $claim,
            'refundPreview' => $refundPreview,
            'deliveryPersons' => $deliveryPersons,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function updateStatus(Request $request, CustomerServiceClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:40'],
            'decision_notes' => ['nullable', 'string', 'max:1000'],
            'approved_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $approvedMinor = isset($validated['approved_amount']) && $validated['approved_amount'] !== ''
            ? StoreAccountService::decimalToMinor($validated['approved_amount'])
            : null;

        try {
            $this->claimService->transitionClaim(
                $claim,
                $validated['status'],
                (int) Auth::guard('admin')->id(),
                $validated['decision_notes'] ?? null,
                $approvedMinor,
                $request->ip()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', "Claim {$claim->case_reference} status updated.");
    }

    public function approveAndCreateRefund(Request $request, CustomerServiceClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_method' => ['required', Rule::in([
                RefundTransaction::METHOD_STRIPE,
                RefundTransaction::METHOD_PAYPAL,
                RefundTransaction::METHOD_COD_MANUAL,
                RefundTransaction::METHOD_ONLINE_TRANSFER,
            ])],
            'booking_customer_payment_id' => ['nullable', 'integer', 'exists:booking_customer_payments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $amountMinor = StoreAccountService::decimalToMinor($validated['amount']);
        $sourcePayment = ! empty($validated['booking_customer_payment_id'])
            ? BookingCustomerPayment::find($validated['booking_customer_payment_id'])
            : null;

        try {
            $refund = $this->refundService->approveRefund(
                $claim->booking,
                $claim,
                $amountMinor,
                $validated['payment_method'],
                (int) Auth::guard('admin')->id(),
                $sourcePayment,
                $validated['notes'] ?? null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', "Refund {$refund->refund_reference} approved and queued for execution.");
    }

    public function executeRefund(RefundTransaction $refund): RedirectResponse
    {
        try {
            $updated = $this->refundService->executeGatewayRefund($refund, (int) Auth::guard('admin')->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', "Refund {$updated->refund_reference} status: {$updated->status}.");
    }

    public function markCodPaid(Request $request, RefundTransaction $refund): RedirectResponse
    {
        $validated = $request->validate([
            'payout_recipient' => ['required', 'string', 'max:190'],
            'payout_method' => ['required', 'string', 'max:80'],
            'external_reference' => ['nullable', 'string', 'max:150'],
        ]);

        $updated = $this->refundService->markCodRefundPaid($refund, $validated, (int) Auth::guard('admin')->id());

        return back()->with('success', "Manual/COD refund {$updated->refund_reference} marked Paid.");
    }

    public function scheduleRewash(Request $request, CustomerServiceClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'collection_delivery_person_id' => ['nullable', 'integer', 'exists:delivery_persons,id'],
            'delivery_delivery_person_id' => ['nullable', 'integer', 'exists:delivery_persons,id'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ]);

        $rateMinor = isset($validated['rate']) && $validated['rate'] !== ''
            ? StoreAccountService::decimalToMinor($validated['rate'])
            : 0;

        $job = $this->claimService->scheduleRewashLogistics(
            $claim,
            (int) Auth::guard('admin')->id(),
            $validated['collection_delivery_person_id'] ?? null,
            $validated['delivery_delivery_person_id'] ?? null,
            $rateMinor
        );

        return back()->with('success', "Rewash logistics scheduled ({$job->job_reference}).");
    }

    public function applyLiability(Request $request, CustomerServiceClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'liability_party' => ['required', Rule::in(['none', 'platform', 'store', 'delivery_person', 'shared'])],
            'store_liability' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'dp_liability' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'delivery_person_id' => ['nullable', 'integer', 'exists:delivery_persons,id'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $storeMinor = isset($validated['store_liability']) && $validated['store_liability'] !== '' ? StoreAccountService::decimalToMinor($validated['store_liability']) : 0;
        $dpMinor = isset($validated['dp_liability']) && $validated['dp_liability'] !== '' ? StoreAccountService::decimalToMinor($validated['dp_liability']) : 0;

        try {
            $this->claimService->applyLiabilityAdjustment(
                $claim,
                $validated['liability_party'],
                $storeMinor,
                $dpMinor,
                $validated['delivery_person_id'] ?? null,
                $validated['reason'],
                (int) Auth::guard('admin')->id()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', "Responsibility and ledger adjustment recorded for {$claim->case_reference}.");
    }

    public function refundsIndex(Request $request): View
    {
        $refunds = RefundTransaction::query()
            ->with(['booking:id,reference,customer_name', 'claim:id,case_reference', 'approvedByAdmin:id,name'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin::refund-returns.refunds.index', [
            'refunds' => $refunds,
            'currencyService' => $this->currencyService,
        ]);
    }
}
