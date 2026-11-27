<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\BookingStoreEarning;
use App\Models\BookingStoreHandover;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\LaundryTag;
use App\Models\LaundryTagEvent;
use App\Services\AdminDashboardAnalyticsService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\Store;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function __construct(
        private readonly AdminDashboardAnalyticsService $analytics,
        private readonly CurrencyService $currencyService,
    ) {}

    private function resolveContext(Request $request): array
    {
        $period = $this->analytics->resolveReportingPeriod(
            $request->input('month'),
            $request->input('preset'),
            $request->input('from_date'),
            $request->input('to_date')
        );
        $currencies = $this->analytics->getAvailableCurrencies();
        $currency = strtoupper((string) ($request->input('currency') ?: ($currencies[0] ?? 'GBP')));

        return compact('period', 'currencies', 'currency');
    }

    /**
     * 1. Overview & Comparisons Report.
     */
    public function index(Request $request): View
    {
        $ctx = $this->resolveContext($request);
        $overview = $this->analytics->getOverviewData($ctx['period'], 'daily');
        $operations = $this->analytics->getOperationsData($ctx['period']);
        $finance = $this->analytics->getFinanceData($ctx['period'], $ctx['currency']);
        $tags = $this->analytics->getTagsData();

        return view('admin::reports.index', array_merge($ctx, compact('overview', 'operations', 'finance', 'tags')));
    }

    /**
     * 2. Transactions & Finance Report.
     */
    public function transactions(Request $request): View
    {
        $ctx = $this->resolveContext($request);
        $from = $ctx['period']['from_utc'];
        $to = $ctx['period']['to_exclusive_utc'];
        $currency = $ctx['currency'];

        $finance = $this->analytics->getFinanceData($ctx['period'], $currency);
        $payments = BookingCustomerPayment::query()
            ->with('booking:id,reference,customer_name')
            ->where('currency', $currency)
            ->whereRaw('COALESCE(verified_at, created_at) >= ? AND COALESCE(verified_at, created_at) < ?', [$from, $to])
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->input('method')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $currencyService = $this->currencyService;

        return view('admin::reports.transactions', array_merge($ctx, compact('finance', 'payments', 'currencyService')));
    }

    /**
     * 3. Store Performance Report.
     */
    public function stores(Request $request): View
    {
        $ctx = $this->resolveContext($request);
        $from = $ctx['period']['from_utc'];
        $to = $ctx['period']['to_exclusive_utc'];
        $currency = $ctx['currency'];

        $stores = Store::orderBy('name')->get()->map(function (Store $st) use ($from, $to, $currency) {
            $received = BookingStoreHandover::where('store_id', $st->id)
                ->where('status', BookingStoreHandover::STATUS_ACCEPTED)
                ->where('accepted_at', '>=', $from)->where('accepted_at', '<', $to)->count();
            $rejected = BookingStoreHandover::where('store_id', $st->id)
                ->where('status', BookingStoreHandover::STATUS_REJECTED)
                ->where('rejected_at', '>=', $from)->where('rejected_at', '<', $to)->count();
            $completed = Booking::where('assigned_store_id', $st->id)
                ->whereIn('booking_status', [Booking::STATUS_STORE_COMPLETE, Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP, Booking::STATUS_ON_DELIVERY, Booking::STATUS_COMPLETED])
                ->where('updated_at', '>=', $from)->where('updated_at', '<', $to)->count();

            $earnings = BookingStoreEarning::where('store_id', $st->id)
                ->where('currency', $currency)->where('is_current', true)
                ->where('created_at', '>=', $from)->where('created_at', '<', $to)
                ->whereHas('booking', fn ($q) => $q->where('booking_status', '!=', Booking::STATUS_CANCELLED))
                ->get(['commission_amount_minor', 'net_store_earnings_minor']);

            return [
                'id' => $st->id,
                'name' => $st->name,
                'store_code' => $st->store_code,
                'status' => $st->status,
                'received' => $received,
                'completed' => $completed,
                'rejected' => $rejected,
                'net_earnings_formatted' => $this->currencyService->formatMinor((int) $earnings->sum('net_store_earnings_minor'), $currency),
                'commission_formatted' => $this->currencyService->formatMinor((int) $earnings->sum('commission_amount_minor'), $currency),
                'net_earnings_minor' => (int) $earnings->sum('net_store_earnings_minor'),
            ];
        });

        return view('admin::reports.stores', array_merge($ctx, compact('stores')));
    }

    /**
     * 4. Delivery Person Performance Report.
     */
    public function deliveryPersons(Request $request): View
    {
        $ctx = $this->resolveContext($request);
        $from = $ctx['period']['from_utc'];
        $to = $ctx['period']['to_exclusive_utc'];
        $currency = $ctx['currency'];

        $deliveryPersons = DeliveryPerson::orderBy('full_name')->get()->map(function (DeliveryPerson $dp) use ($from, $to, $currency) {
            $colCompleted = DeliveryAssignment::where('delivery_person_id', $dp->id)
                ->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
                ->where('status', DeliveryAssignment::STATUS_COMPLETED)
                ->where('completed_at', '>=', $from)->where('completed_at', '<', $to)->count();

            $finCompleted = DeliveryAssignment::where('delivery_person_id', $dp->id)
                ->where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)
                ->where('status', DeliveryAssignment::STATUS_COMPLETED)
                ->where('completed_at', '>=', $from)->where('completed_at', '<', $to)->count();

            $earningsMinor = (int) DeliveryPersonLedgerEntry::where('delivery_person_id', $dp->id)
                ->where('currency', $currency)->where('direction', 'credit')
                ->where('occurred_at', '>=', $from)->where('occurred_at', '<', $to)
                ->sum('amount_minor');

            $codIn = (float) DeliveryPersonCashTransaction::where('delivery_person_id', $dp->id)
                ->where('currency', $currency)->where('direction', DeliveryPersonCashTransaction::DIRECTION_CREDIT)->sum('amount');
            $codOut = (float) DeliveryPersonCashTransaction::where('delivery_person_id', $dp->id)
                ->where('currency', $currency)->where('direction', DeliveryPersonCashTransaction::DIRECTION_DEBIT)->sum('amount');
            $custodyMinor = (int) round(max(0.0, $codIn - $codOut) * 100);

            return [
                'id' => $dp->id,
                'code' => $dp->code,
                'name' => $dp->full_name,
                'work_status' => $dp->work_status,
                'collection_completed' => $colCompleted,
                'final_completed' => $finCompleted,
                'earnings_minor' => $earningsMinor,
                'earnings_formatted' => $this->currencyService->formatMinor($earningsMinor, $currency),
                'cod_custody_formatted' => $this->currencyService->formatMinor($custodyMinor, $currency),
            ];
        });

        return view('admin::reports.delivery-persons', array_merge($ctx, compact('deliveryPersons')));
    }

    /**
     * 5. Problem & Issue Tracker Report.
     */
    public function problems(Request $request): View
    {
        $ctx = $this->resolveContext($request);
        $from = $ctx['period']['from_utc'];
        $to = $ctx['period']['to_exclusive_utc'];

        $cancelledBookings = Booking::where('booking_status', Booking::STATUS_CANCELLED)
            ->where('updated_at', '>=', $from)->where('updated_at', '<', $to)
            ->orderByDesc('updated_at')->limit(25)->get();

        $rejectedHandovers = BookingStoreHandover::with(['booking:id,reference', 'store:id,name', 'collectionDeliveryPerson:id,full_name'])
            ->where('status', BookingStoreHandover::STATUS_REJECTED)
            ->where('rejected_at', '>=', $from)->where('rejected_at', '<', $to)
            ->orderByDesc('rejected_at')->limit(25)->get();

        $tagIssues = LaundryTagEvent::with(['tag:id,tag_code', 'booking:id,reference', 'store:id,name'])
            ->whereIn('event_type', [
                LaundryTagEvent::EVENT_HANDOVER_VERIFICATION_ISSUE,
                LaundryTagEvent::EVENT_REPORTED_LOST,
                LaundryTagEvent::EVENT_REPORTED_DAMAGED,
                LaundryTagEvent::EVENT_VOIDED,
            ])
            ->where('occurred_at', '>=', $from)->where('occurred_at', '<', $to)
            ->orderByDesc('occurred_at')->limit(25)->get();

        $lostDamagedCount = LaundryTag::whereIn('status', [LaundryTag::STATUS_LOST, LaundryTag::STATUS_DAMAGED, LaundryTag::STATUS_VOIDED])->count();

        return view('admin::reports.problems', array_merge($ctx, compact('cancelledBookings', 'rejectedHandovers', 'tagIssues', 'lostDamagedCount')));
    }

    /**
     * CSV Export for Transactions Report.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $ctx = $this->resolveContext($request);
        $from = $ctx['period']['from_utc'];
        $to = $ctx['period']['to_exclusive_utc'];
        $currency = $ctx['currency'];

        $rows = BookingCustomerPayment::query()
            ->with('booking:id,reference,customer_name')
            ->where('currency', $currency)
            ->whereRaw('COALESCE(verified_at, created_at) >= ? AND COALESCE(verified_at, created_at) < ?', [$from, $to])
            ->orderByDesc('id')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Payment ID', 'Booking Ref', 'Method', 'Status', 'Currency', 'Amount Minor', 'Gateway Fee Minor', 'Date']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->booking?->reference ?? ('#' . $r->booking_id),
                    $r->payment_method,
                    $r->status,
                    $r->currency,
                    $r->amount_minor,
                    $r->gateway_fee_minor ?? '',
                    ($r->verified_at ?? $r->created_at)?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        }, "freshgo-transactions-{$ctx['period']['month']}-{$currency}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Unified handler for the remaining specialized reports in the 18-Report Center.
     */
    public function showReport(Request $request, string $reportType): View
    {
        $ctx = $this->resolveContext($request);
        $from = $ctx['period']['from_utc'];
        $to = $ctx['period']['to_exclusive_utc'];
        $currency = $ctx['currency'];

        $overview = $this->analytics->getOverviewData($ctx['period'], 'daily');
        $operations = $this->analytics->getOperationsData($ctx['period']);
        $finance = $this->analytics->getFinanceData($ctx['period'], $currency);
        $tags = $this->analytics->getTagsData();
        $activity = $this->analytics->getRecentActivity(25);

        $meta = match ($reportType) {
            'overview' => [
                'title' => 'Executive Overview',
                'subtitle' => 'Complete business picture across bookings, operations, payments, and liabilities.',
                'kpis' => [
                    ['label' => 'New Bookings', 'value' => $overview['kpis']['new_bookings'], 'color' => 'primary'],
                    ['label' => 'Deliveries Completed', 'value' => $overview['kpis']['deliveries_completed'], 'color' => 'success'],
                    ['label' => "Verified Receipts ({$currency})", 'value' => $finance['kpis']['verified_receipts_formatted'], 'color' => 'dark'],
                    ['label' => 'COD Cash Custody', 'value' => $finance['kpis']['cod_custody_formatted'], 'color' => 'warning'],
                ],
                'note' => null,
            ],
            'website' => [
                'title' => 'Website Performance',
                'subtitle' => 'Contact enquiries and booking funnel completions.',
                'kpis' => [
                    ['label' => 'Contact Enquiries (Period)', 'value' => \App\Models\ContactEnquiry::where('created_at', '>=', $from)->where('created_at', '<', $to)->count(), 'color' => 'primary'],
                    ['label' => 'Bookings Created (Period)', 'value' => $overview['kpis']['new_bookings'], 'color' => 'success'],
                    ['label' => 'Page Views / Visitors', 'value' => 'Requires Tracking', 'color' => 'secondary'],
                    ['label' => 'Conversion Rate', 'value' => 'Requires Tracking', 'color' => 'secondary'],
                ],
                'note' => 'Visitor page-view tracking is not yet instrumented; showing verified Contact Enquiries and Booking Completions only.',
            ],
            'customers' => [
                'title' => 'Customer Growth',
                'subtitle' => 'Customer booking frequency and registered accounts.',
                'kpis' => [
                    ['label' => 'Unique Booking Emails (Period)', 'value' => Booking::where('created_at', '>=', $from)->where('created_at', '<', $to)->distinct('customer_email')->count('customer_email'), 'color' => 'primary'],
                    ['label' => 'Total Bookings (Period)', 'value' => $overview['kpis']['new_bookings'], 'color' => 'success'],
                    ['label' => 'Registered Users', 'value' => \App\Models\User::count(), 'color' => 'dark'],
                    ['label' => 'Cancelled Bookings', 'value' => $overview['kpis']['cancelled_bookings'], 'color' => 'danger'],
                ],
                'note' => null,
            ],
            'bookings' => [
                'title' => 'Booking Analytics',
                'subtitle' => 'Lifecycle volume, status distribution, and completions.',
                'kpis' => [
                    ['label' => 'Bookings Created', 'value' => $overview['kpis']['new_bookings'], 'color' => 'primary'],
                    ['label' => 'Store Completed', 'value' => $overview['kpis']['store_completed'], 'color' => 'success'],
                    ['label' => 'Deliveries Completed', 'value' => $overview['kpis']['deliveries_completed'], 'color' => 'info'],
                    ['label' => 'Cancelled', 'value' => $overview['kpis']['cancelled_bookings'], 'color' => 'danger'],
                ],
                'note' => null,
            ],
            'bottlenecks' => [
                'title' => 'Workflow Bottlenecks',
                'subtitle' => 'Live pending operational stages and tag verification backlogs.',
                'kpis' => [
                    ['label' => 'Active Collection Jobs', 'value' => $operations['operational_kpis']['active_collection_jobs'], 'color' => 'primary'],
                    ['label' => 'Store Processing Pending', 'value' => $operations['operational_kpis']['store_processing_pending'], 'color' => 'warning'],
                    ['label' => 'Pending QR Tag Completions', 'value' => $operations['operational_kpis']['pending_tag_verification'], 'color' => 'danger'],
                    ['label' => 'Active Final Delivery Jobs', 'value' => $operations['operational_kpis']['active_final_jobs'], 'color' => 'info'],
                ],
                'note' => null,
            ],
            'tags' => [
                'title' => 'QR Tag Analytics',
                'subtitle' => 'Tag inventory, allocations, and Store bag completion.',
                'kpis' => [
                    ['label' => 'Total Generated', 'value' => $tags['total_generated'], 'color' => 'dark'],
                    ['label' => 'Available Stock', 'value' => $tags['available'], 'color' => 'success'],
                    ['label' => 'Pending Store Tags', 'value' => $tags['pending_store_tags'], 'color' => 'warning'],
                    ['label' => 'Completed Store Tags', 'value' => $tags['completed_store_tags'], 'color' => 'primary'],
                ],
                'note' => null,
            ],
            'profitability' => [
                'title' => 'Revenue & Profitability',
                'subtitle' => 'Recorded-cost contribution from verified receipts, commissions, and DP earnings.',
                'kpis' => [
                    ['label' => "Verified Receipts ({$currency})", 'value' => $finance['kpis']['verified_receipts_formatted'], 'color' => 'success'],
                    ['label' => 'Platform Commission', 'value' => $finance['kpis']['platform_commission_formatted'], 'color' => 'dark'],
                    ['label' => 'DP Earnings Expense', 'value' => $finance['kpis']['dp_earnings_formatted'], 'color' => 'primary'],
                    ['label' => 'Stripe Fees', 'value' => $finance['kpis']['stripe_fee_formatted'], 'color' => 'warning'],
                ],
                'note' => 'Reported as Recorded-Cost Contribution; off-platform overhead costs are not tracked in the database.',
            ],
            'earnings' => [
                'title' => 'Commission & Earnings',
                'subtitle' => 'Platform commission, Store Share, and Delivery Person job earnings.',
                'kpis' => [
                    ['label' => 'Platform Commission', 'value' => $finance['kpis']['platform_commission_formatted'], 'color' => 'dark'],
                    ['label' => 'Store Share Earned', 'value' => $finance['kpis']['store_share_formatted'], 'color' => 'success'],
                    ['label' => 'DP Earnings Credited', 'value' => $finance['kpis']['dp_earnings_formatted'], 'color' => 'primary'],
                    ['label' => 'Verified Receipts', 'value' => $finance['kpis']['verified_receipts_formatted'], 'color' => 'info'],
                ],
                'note' => null,
            ],
            'withdrawals' => [
                'title' => 'Withdrawals & Settlements',
                'subtitle' => 'Store and Delivery Person withdrawal requests, approvals, and settlements.',
                'kpis' => [
                    ['label' => 'Store Pending', 'value' => $finance['withdrawals']['store_pending'], 'color' => 'warning'],
                    ['label' => 'Store Settled', 'value' => $finance['withdrawals']['store_settled'], 'color' => 'success'],
                    ['label' => 'DP Pending', 'value' => $finance['withdrawals']['dp_pending'], 'color' => 'warning'],
                    ['label' => 'DP Settled', 'value' => $finance['withdrawals']['dp_settled'], 'color' => 'success'],
                ],
                'note' => null,
            ],
            'cash-custody' => [
                'title' => 'Cash Custody',
                'subtitle' => 'Authoritative COD cash held by Delivery Persons (separate from DP earnings).',
                'kpis' => [
                    ['label' => "Current COD Custody ({$currency})", 'value' => $finance['kpis']['cod_custody_formatted'], 'color' => 'primary'],
                    ['label' => 'DP Job Earnings (Period)', 'value' => $finance['kpis']['dp_earnings_formatted'], 'color' => 'success'],
                    ['label' => 'Active Collection Jobs', 'value' => $operations['operational_kpis']['active_collection_jobs'], 'color' => 'info'],
                    ['label' => 'Active Final Jobs', 'value' => $operations['operational_kpis']['active_final_jobs'], 'color' => 'dark'],
                ],
                'note' => null,
            ],
            'stores-financials' => [
                'title' => 'Store Financials',
                'subtitle' => 'Store Share earned, platform commission deducted, and withdrawals.',
                'kpis' => [
                    ['label' => "Store Share ({$currency})", 'value' => $finance['kpis']['store_share_formatted'], 'color' => 'success'],
                    ['label' => 'Platform Commission', 'value' => $finance['kpis']['platform_commission_formatted'], 'color' => 'dark'],
                    ['label' => 'Store Withdrawals Pending', 'value' => $finance['withdrawals']['store_pending'], 'color' => 'warning'],
                    ['label' => 'Store Withdrawals Settled', 'value' => $finance['withdrawals']['store_settled'], 'color' => 'primary'],
                ],
                'note' => null,
            ],
            'delivery-persons-earnings' => [
                'title' => 'Delivery Person Earnings',
                'subtitle' => 'Credited Collection and Final Delivery job earnings.',
                'kpis' => [
                    ['label' => "DP Earnings ({$currency})", 'value' => $finance['kpis']['dp_earnings_formatted'], 'color' => 'primary'],
                    ['label' => 'DP Withdrawals Pending', 'value' => $finance['withdrawals']['dp_pending'], 'color' => 'warning'],
                    ['label' => 'DP Withdrawals Settled', 'value' => $finance['withdrawals']['dp_settled'], 'color' => 'success'],
                    ['label' => 'COD Cash Custody', 'value' => $finance['kpis']['cod_custody_formatted'], 'color' => 'dark'],
                ],
                'note' => null,
            ],
            'delivery-comparison' => [
                'title' => 'Collection vs Final Delivery',
                'subtitle' => 'Independent comparison of Customer → Store and Store → Customer jobs.',
                'kpis' => [
                    ['label' => 'Collections Assigned', 'value' => $operations['delivery_jobs']['collection_assigned'], 'color' => 'primary'],
                    ['label' => 'Collections Completed', 'value' => $operations['delivery_jobs']['collection_completed'], 'color' => 'success'],
                    ['label' => 'Final Assigned', 'value' => $operations['delivery_jobs']['final_assigned'], 'color' => 'info'],
                    ['label' => 'Final Completed', 'value' => $operations['delivery_jobs']['final_completed'], 'color' => 'dark'],
                ],
                'note' => null,
            ],
            default => [
                'title' => 'Activity & Audit Trail',
                'subtitle' => 'Recorded booking and QR tag lifecycle events.',
                'kpis' => [
                    ['label' => 'Recent Events Loaded', 'value' => count($activity), 'color' => 'primary'],
                    ['label' => 'New Bookings (Period)', 'value' => $overview['kpis']['new_bookings'], 'color' => 'success'],
                    ['label' => 'QR Tags Generated', 'value' => $tags['total_generated'], 'color' => 'dark'],
                    ['label' => 'Active Stores', 'value' => $operations['operational_kpis']['active_stores'], 'color' => 'info'],
                ],
                'note' => null,
            ],
        };

        return view('admin::reports.dynamic', array_merge($ctx, compact('reportType', 'meta', 'overview', 'operations', 'finance', 'tags', 'activity')));
    }
}
