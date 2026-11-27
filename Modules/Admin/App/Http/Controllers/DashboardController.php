<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardAnalyticsService $analytics,
    ) {}

    private function resolvePeriodFromRequest(Request $request): array
    {
        return $this->analytics->resolveReportingPeriod(
            $request->input('month'),
            $request->input('preset'),
            $request->input('from_date'),
            $request->input('to_date')
        );
    }

    public function index(Request $request): View
    {
        $admin = Auth::guard('admin')->user();
        $period = $this->resolvePeriodFromRequest($request);
        $currencies = $this->analytics->getAvailableCurrencies();
        $selectedCurrency = strtoupper((string) ($request->input('currency') ?: ($currencies[0] ?? 'GBP')));

        $overview = $this->analytics->getOverviewData($period, 'daily');
        $operations = $this->analytics->getOperationsData($period);
        $finance = $this->analytics->getFinanceData($period, $selectedCurrency);
        $tags = $this->analytics->getTagsData();
        $activity = $this->analytics->getRecentActivity(12);

        return view('admin::dashboard', compact(
            'admin',
            'period',
            'currencies',
            'selectedCurrency',
            'overview',
            'operations',
            'finance',
            'tags',
            'activity'
        ));
    }

    public function overview(Request $request): JsonResponse
    {
        $period = $this->resolvePeriodFromRequest($request);
        $groupBy = in_array($request->input('group_by'), ['daily', 'weekly', 'monthly'], true)
            ? $request->input('group_by')
            : 'daily';

        return response()->json([
            'success' => true,
            'period' => [
                'month' => $period['month'],
                'month_label' => $period['month_label'],
                'prev_month' => $period['prev_month'],
                'next_month' => $period['next_month'],
                'from_date' => $period['from_date'],
                'to_date_inclusive' => $period['to_date_inclusive'],
            ],
            'data' => $this->analytics->getOverviewData($period, $groupBy),
        ]);
    }

    public function operations(Request $request): JsonResponse
    {
        $period = $this->resolvePeriodFromRequest($request);

        return response()->json([
            'success' => true,
            'data' => $this->analytics->getOperationsData($period),
        ]);
    }

    public function finance(Request $request): JsonResponse
    {
        $period = $this->resolvePeriodFromRequest($request);
        $currency = $request->input('currency');

        return response()->json([
            'success' => true,
            'data' => $this->analytics->getFinanceData($period, $currency),
        ]);
    }

    public function tags(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->analytics->getTagsData(),
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $limit = max(5, min(50, (int) $request->input('limit', 15)));

        return response()->json([
            'success' => true,
            'data' => $this->analytics->getRecentActivity($limit),
        ]);
    }
}
