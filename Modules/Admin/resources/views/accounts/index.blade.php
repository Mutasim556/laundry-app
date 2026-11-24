@extends('admin::layouts.app')

@push('styles')
<style>
    .ca-page-root {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow-x: clip;
        background-color: #F6F8FC;
        font-size: 13.5px;
    }
    .ca-page-title {
        font-size: 22px;
        font-weight: 700;
        color: #171717;
        line-height: 1.25;
    }
    .ca-section-title {
        font-size: 15px;
        font-weight: 700;
        color: #171717;
    }
    .ca-muted {
        color: #475569;
    }
    .ca-money {
        font-variant-numeric: tabular-nums;
        font-weight: 700;
    }
    .ca-card {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    }
    .ca-stat-card {
        display: block;
        text-decoration: none;
        border-radius: 12px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .ca-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.07);
    }
    .ca-stat-amount {
        font-size: 21px;
        font-weight: 700;
        color: #171717;
        font-variant-numeric: tabular-nums;
        line-height: 1.2;
    }
    .ca-stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .ca-filter-control {
        height: 38px;
        font-size: 12.5px;
        border-radius: 9px;
        color: #171717;
    }
    .ca-table-scroll {
        display: block;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #94A3B8 #F8FAFC;
        border-top: 1px solid #E2E8F0;
    }
    .ca-table-scroll::-webkit-scrollbar {
        height: 8px;
    }
    .ca-table-scroll::-webkit-scrollbar-track {
        background: #F8FAFC;
    }
    .ca-table-scroll::-webkit-scrollbar-thumb {
        background: #94A3B8;
        border-radius: 999px;
    }
    .ca-table {
        width: 100%;
        min-width: 980px;
        margin-bottom: 0;
        font-size: 12.5px;
    }
    .ca-table thead th {
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        background-color: #F8FAFC;
        padding: 9px 10px;
        white-space: nowrap;
        border-bottom: 1px solid #E2E8F0;
    }
    .ca-table tbody td {
        padding: 9px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #F1F5F9;
        color: #171717;
    }
    .ca-table tbody tr:hover {
        background-color: #FFFDF7;
    }
    .ca-breakdown-table {
        width: 100%;
        min-width: 380px;
        margin-bottom: 0;
        font-size: 13px;
    }
    .ca-breakdown-table td {
        padding: 7px 0;
        border-bottom: 1px solid #F1F5F9;
    }
    .ca-breakdown-table tr:last-child td {
        border-bottom: 0;
    }
</style>
@endpush

@section('content')
@php
    $baseMonthParams = array_filter([
        'currency' => $selectedCurrency,
        'transaction_type' => request('transaction_type'),
        'classification' => request('classification'),
        'status' => request('status'),
        'payment_method' => request('payment_method'),
        'store_id' => request('store_id'),
        'delivery_person_id' => request('delivery_person_id'),
        'search' => request('search'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<div class="container-fluid py-3 ca-page-root">
    <!-- SECTION 1: HEADER & MONTH-WISE NAVIGATION -->
    <div class="ca-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="ca-page-title mb-0">
                    <i class="mdi mdi-wallet me-1" style="color: #D97706;"></i>Admin Current Account
                </h4>
                <div class="small ca-muted mt-1">
                    Monthly Financial Overview &mdash; <strong class="text-dark">{{ $period['month_label'] }}</strong> ({{ $selectedCurrency }})
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Month Navigation -->
                <div class="btn-group btn-group-sm" role="group" aria-label="Month navigation">
                    <a href="{{ route('admin.current-account.index', array_merge($baseMonthParams, ['month' => $period['prev_month']])) }}"
                       class="btn btn-light border ca-filter-control d-inline-flex align-items-center px-2"
                       title="Previous Month">
                        <i class="mdi mdi-chevron-left"></i> Prev
                    </a>
                    @if($period['is_current_or_future'])
                        <button type="button" class="btn btn-light border ca-filter-control d-inline-flex align-items-center px-2" disabled title="Next Month unavailable">
                            Next <i class="mdi mdi-chevron-right"></i>
                        </button>
                    @else
                        <a href="{{ route('admin.current-account.index', array_merge($baseMonthParams, ['month' => $period['next_month']])) }}"
                           class="btn btn-light border ca-filter-control d-inline-flex align-items-center px-2"
                           title="Next Month">
                            Next <i class="mdi mdi-chevron-right"></i>
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('admin.current-account.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="month"
                           name="month"
                           value="{{ $period['month'] }}"
                           max="{{ $period['current_month'] }}"
                           class="form-control form-control-sm ca-filter-control"
                           style="width: auto;"
                           onchange="this.form.submit()"
                           aria-label="Select Month">
                    <select name="currency" class="form-select form-select-sm ca-filter-control" style="width: auto;" onchange="this.form.submit()" aria-label="Currency">
                        @foreach($availableCurrencies as $cur)
                            <option value="{{ $cur }}" {{ $cur === $selectedCurrency ? 'selected' : '' }}>{{ $cur }}</option>
                        @endforeach
                    </select>
                </form>

                <a href="{{ route('admin.current-account.index', array_merge($baseMonthParams, ['month' => $period['current_month']])) }}"
                   class="btn btn-sm btn-light border ca-filter-control d-inline-flex align-items-center px-2">
                    <i class="mdi mdi-calendar me-1"></i>Current Month
                </a>
                <a href="{{ route('admin.current-account.index', request()->query()) }}"
                   class="btn btn-sm btn-light border ca-filter-control d-inline-flex align-items-center px-2">
                    <i class="mdi mdi-refresh me-1"></i>Refresh
                </a>
                <a href="{{ route('admin.current-account.export', request()->query()) }}"
                   class="btn btn-sm btn-outline-dark ca-filter-control d-inline-flex align-items-center px-2">
                    <i class="mdi mdi-download me-1"></i>Export CSV
                </a>
                @can('adjust-admin-accounts', 'admin')
                    <button type="button" class="btn btn-sm fw-bold ca-filter-control d-inline-flex align-items-center px-3" style="background-color: #FFC629; color: #171717;" data-bs-toggle="modal" data-bs-target="#adminAdjustmentModal">
                        <i class="mdi mdi-plus-circle-outline me-1"></i>Adjustment
                    </button>
                @endcan
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- SECTION 2: MONTHLY SUMMARY CARDS (4 per row on desktop, click to drill down) -->
    <div class="row g-2 g-md-3 mb-3">
        <!-- 1. Customer Payments Received -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('admin.current-account.index', ['currency' => $selectedCurrency, 'month' => $period['month'], 'classification' => 'Cash Receipt']) }}#txLedgerCard"
               class="ca-stat-card p-3 h-100" style="background-color: #ECFDF5; border-left: 4px solid #16A34A;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold text-success">Customer Payments Received</div>
                        <div class="ca-stat-amount mt-1">{{ $currencyService->formatMinor((int) $reportSummary['customer_receipts_platform_minor'], $selectedCurrency) }}</div>
                        <div class="ca-muted mt-1" style="font-size: 0.74rem;">{{ $period['month_label'] }} verified receipts</div>
                    </div>
                    <span class="ca-stat-icon" style="background-color: #DCFCE7; color: #16A34A;"><i class="mdi mdi-cash-multiple"></i></span>
                </div>
            </a>
        </div>

        <!-- 2. Store Commission Earned -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('admin.current-account.index', ['currency' => $selectedCurrency, 'month' => $period['month'], 'transaction_type' => 'store_commission_earned']) }}#txLedgerCard"
               class="ca-stat-card p-3 h-100" style="background-color: #F5F3FF; border-left: 4px solid #8B5CF6;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold" style="color: #6D28D9;">Store Commission Earned</div>
                        <div class="ca-stat-amount mt-1">{{ $currencyService->formatMinor((int) $reportSummary['platform_commission_minor'], $selectedCurrency) }}</div>
                        <div class="ca-muted mt-1" style="font-size: 0.74rem;">{{ $period['month_label'] }} finalized snapshots</div>
                    </div>
                    <span class="ca-stat-icon" style="background-color: #EDE9FE; color: #7C3AED;"><i class="mdi mdi-percent"></i></span>
                </div>
            </a>
        </div>

        <!-- 3. Delivery Person Earnings -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('admin.current-account.index', ['currency' => $selectedCurrency, 'month' => $period['month'], 'classification' => 'Recorded Expense']) }}#txLedgerCard"
               class="ca-stat-card p-3 h-100" style="background-color: #EFF6FF; border-left: 4px solid #3B82F6;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold text-primary">Delivery Person Earnings</div>
                        <div class="ca-stat-amount mt-1">{{ $currencyService->formatMinor((int) $reportSummary['dp_total_earned_minor'], $selectedCurrency) }}</div>
                        <div class="ca-muted mt-1" style="font-size: 0.74rem;">Collection + Final Delivery ({{ $period['month_label'] }})</div>
                    </div>
                    <span class="ca-stat-icon" style="background-color: #DBEAFE; color: #2563EB;"><i class="mdi mdi-motorbike"></i></span>
                </div>
            </a>
        </div>

        <!-- 4. Delivery Person Payouts -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('admin.current-account.index', ['currency' => $selectedCurrency, 'month' => $period['month'], 'transaction_type' => 'delivery_person_payout']) }}#txLedgerCard"
               class="ca-stat-card p-3 h-100" style="background-color: #FEF2F2; border-left: 4px solid #EF4444;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold text-danger">Delivery Person Payouts</div>
                        <div class="ca-stat-amount mt-1">{{ $currencyService->formatMinor((int) $reportSummary['dp_payouts_paid_minor'], $selectedCurrency) }}</div>
                        <div class="ca-muted mt-1" style="font-size: 0.74rem;">Completed payouts in {{ $period['month_label'] }}</div>
                    </div>
                    <span class="ca-stat-icon" style="background-color: #FEE2E2; color: #DC2626;"><i class="mdi mdi-bank"></i></span>
                </div>
            </a>
        </div>

        <!-- 5. Store Settlements -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('admin.current-account.index', ['currency' => $selectedCurrency, 'month' => $period['month'], 'transaction_type' => 'store_settlement_paid']) }}#txLedgerCard"
               class="ca-stat-card p-3 h-100" style="background-color: #ECFEFF; border-left: 4px solid #06B6D4;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold" style="color: #0E7490;">Store Settlements Paid</div>
                        <div class="ca-stat-amount mt-1">{{ $currencyService->formatMinor((int) $reportSummary['store_payouts_paid_minor'], $selectedCurrency) }}</div>
                        <div class="ca-muted mt-1" style="font-size: 0.74rem;">Current payable: {{ $currencyService->formatMinor((int) $reportSummary['store_payables_outstanding_minor'], $selectedCurrency) }}</div>
                    </div>
                    <span class="ca-stat-icon" style="background-color: #CFFAFE; color: #0891B2;"><i class="mdi mdi-store"></i></span>
                </div>
            </a>
        </div>

        <!-- 6. Refunds Recorded -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('admin.current-account.index', ['currency' => $selectedCurrency, 'month' => $period['month'], 'classification' => 'Refund']) }}#txLedgerCard"
               class="ca-stat-card p-3 h-100" style="background-color: #FFF1F2; border-left: 4px solid #EF4444;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold text-danger">Refunds Recorded</div>
                        <div class="ca-stat-amount mt-1">{{ $currencyService->formatMinor((int) $reportSummary['refunds_minor'], $selectedCurrency) }}</div>
                        <div class="ca-muted mt-1" style="font-size: 0.74rem;">Total disbursements: {{ $currencyService->formatMinor((int) $reportSummary['recorded_disbursements_minor'], $selectedCurrency) }}</div>
                    </div>
                    <span class="ca-stat-icon" style="background-color: #FFE4E6; color: #DC2626;"><i class="mdi mdi-undo"></i></span>
                </div>
            </a>
        </div>

        <!-- 7. Net Recorded Cash Movement -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ca-stat-card p-3 h-100" style="background-color: #FFFFFF; border-left: 4px solid #171717;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold text-dark">Net Recorded Cash Movement</div>
                        <div class="ca-stat-amount mt-1">{{ $currencyService->formatMinor((int) $reportSummary['net_recorded_cash_movement_minor'], $selectedCurrency) }}</div>
                        <div class="ca-muted mt-1" style="font-size: 0.74rem;">{{ $period['month_label'] }} receipts minus outflows</div>
                    </div>
                    <span class="ca-stat-icon" style="background-color: #F1F5F9; color: #171717;"><i class="mdi mdi-scale-balance"></i></span>
                </div>
            </div>
        </div>

        <!-- 8. Outstanding Receivables (Selected Month) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ca-stat-card p-3 h-100" style="background-color: #FFFBEB; border-left: 4px solid #F59E0B;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold text-warning-emphasis">Outstanding Receivables</div>
                        <div class="ca-stat-amount mt-1">{{ $currencyService->formatMinor((int) $reportSummary['customer_receivables_minor'], $selectedCurrency) }}</div>
                        <div class="ca-muted mt-1" style="font-size: 0.74rem;">Unpaid bookings in {{ $period['month_label'] }}</div>
                    </div>
                    <span class="ca-stat-icon" style="background-color: #FEF3C7; color: #D97706;"><i class="mdi mdi-clock"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3: MONTHLY FINANCIAL BREAKDOWN (2-column desktop, 1-column mobile) -->
    <div class="row g-3 mb-3">
        <!-- Customer Payments -->
        <div class="col-12 col-xl-6">
            <div class="ca-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="ca-section-title mb-0"><i class="mdi mdi-credit-card text-success me-1"></i>Customer Payments ({{ $period['month_label'] }})</h6>
                        <div class="ca-muted" style="font-size: 0.75rem;">Confirmed gateway receipts vs custodial cash collections</div>
                    </div>
                    <span class="badge bg-success-subtle text-success">Receipts</span>
                </div>
                @php $pm = $reportSummary['payment_method_breakdown']; @endphp
                <div class="ca-table-scroll border-0">
                    <table class="ca-breakdown-table">
                        <tbody>
                            <tr>
                                <td class="ca-muted">Stripe Receipts (Confirmed)</td>
                                <td class="text-end ca-money text-dark">{{ $currencyService->formatMinor($pm['stripe'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">PayPal Receipts (Confirmed)</td>
                                <td class="text-end ca-money text-dark">{{ $currencyService->formatMinor($pm['paypal'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Online Transfer (Verified)</td>
                                <td class="text-end ca-money text-dark">{{ $currencyService->formatMinor($pm['online_transfer'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Cash on Delivery Collected by DPs (Custody)</td>
                                <td class="text-end ca-money text-warning-emphasis">{{ $currencyService->formatMinor($pm['cash_in_hand'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Pending / Unverified Gateway Attempts</td>
                                <td class="text-end ca-money ca-muted">{{ $currencyService->formatMinor($pm['pending_gateway'], $selectedCurrency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Store Financials -->
        <div class="col-12 col-xl-6">
            <div class="ca-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="ca-section-title mb-0"><i class="mdi mdi-store text-warning-emphasis me-1"></i>Store Financials ({{ $period['month_label'] }})</h6>
                        <div class="ca-muted" style="font-size: 0.75rem;">Commissions earned, store shares, and current payable snapshot</div>
                    </div>
                    <span class="badge" style="background-color: #EDE9FE; color: #6D28D9;">Stores</span>
                </div>
                <div class="ca-table-scroll border-0">
                    <table class="ca-breakdown-table">
                        <tbody>
                            <tr>
                                <td class="ca-muted">Eligible Service Revenue Base</td>
                                <td class="text-end ca-money text-dark">{{ $currencyService->formatMinor((int) $reportSummary['eligible_service_revenue_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Platform Commission Earned</td>
                                <td class="text-end ca-money" style="color: #6D28D9;">{{ $currencyService->formatMinor((int) $reportSummary['platform_commission_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Store Share Generated</td>
                                <td class="text-end ca-money text-dark">{{ $currencyService->formatMinor((int) $reportSummary['store_share_total_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Store Settlements Recorded ({{ $period['month_label'] }})</td>
                                <td class="text-end ca-money text-danger">{{ $currencyService->formatMinor((int) $reportSummary['store_payouts_paid_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-dark">Outstanding Store Payables ({{ $period['month_label'] }})</td>
                                <td class="text-end ca-money text-warning-emphasis">{{ $currencyService->formatMinor((int) $reportSummary['store_payables_outstanding_minor'], $selectedCurrency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delivery Person Financials -->
        <div class="col-12 col-xl-6">
            <div class="ca-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="ca-section-title mb-0"><i class="mdi mdi-motorbike text-primary me-1"></i>Delivery Person Financials ({{ $period['month_label'] }})</h6>
                        <div class="ca-muted" style="font-size: 0.75rem;">Separate Collection vs Final Delivery earnings, payouts, and COD custody</div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary">Delivery</span>
                </div>
                <div class="ca-table-scroll border-0">
                    <table class="ca-breakdown-table">
                        <tbody>
                            <tr>
                                <td class="ca-muted">Collection Job Earnings</td>
                                <td class="text-end ca-money text-dark">{{ $currencyService->formatMinor((int) $reportSummary['dp_collection_earned_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Final Delivery Job Earnings</td>
                                <td class="text-end ca-money text-dark">{{ $currencyService->formatMinor((int) $reportSummary['dp_final_earned_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Actual Payouts Paid ({{ $period['month_label'] }})</td>
                                <td class="text-end ca-money text-danger">{{ $currencyService->formatMinor((int) $reportSummary['dp_payouts_paid_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Unpaid DP Payables ({{ $period['month_label'] }})</td>
                                <td class="text-end ca-money text-primary">{{ $currencyService->formatMinor((int) $reportSummary['dp_payables_outstanding_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="ca-muted">Cash Remitted to Business ({{ $period['month_label'] }})</td>
                                <td class="text-end ca-money text-success">{{ $currencyService->formatMinor((int) $reportSummary['customer_cash_remitted_minor'], $selectedCurrency) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-dark">Cash Held by DPs ({{ $period['month_label'] }})</td>
                                <td class="text-end ca-money" style="color: #0891B2;">{{ $currencyService->formatMinor((int) $reportSummary['cash_held_by_dp_minor'], $selectedCurrency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 12-Month Trend Visualization -->
        <div class="col-12 col-xl-6">
            <div class="ca-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="ca-section-title mb-0"><i class="mdi mdi-chart-line me-1" style="color: #8B5CF6;"></i>Financial Activity — Last 12 Months</h6>
                        <div class="ca-muted" style="font-size: 0.75rem;">Receipts, recorded cash outflows, and store commissions ({{ $selectedCurrency }})</div>
                    </div>
                    <span class="badge" style="background-color: #EDE9FE; color: #6D28D9;">12M Trend</span>
                </div>
                <div id="caMonthlyTrendChart" style="min-height: 205px;"></div>
            </div>
        </div>
    </div>

    <!-- SECTION 3B: STRIPE PAYMENT SUMMARY & PROCESSING FEE BREAKDOWN (SELECTED MONTH) -->
    @php $stripeSum = $reportSummary['stripe_summary'] ?? ['confirmed_count' => 0, 'reconciled_count' => 0, 'pending_count' => 0, 'by_settlement_currency' => [], 'transactions' => []]; @endphp
    <div class="ca-card mb-3">
        <div class="p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="ca-section-title mb-0"><i class="mdi mdi-credit-card text-primary me-1"></i>Stripe Payment Summary &mdash; {{ $period['month_label'] }}</h6>
                    <div class="ca-muted" style="font-size: 0.75rem;">Actual Stripe Balance Transaction fees (processing expense, separate from store commission &amp; DP earnings).</div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success">{{ $stripeSum['reconciled_count'] }} Reconciled</span>
                    @if($stripeSum['pending_count'] > 0)
                        <span class="badge bg-warning-subtle text-warning-emphasis">{{ $stripeSum['pending_count'] }} Fee Pending Reconciliation</span>
                    @endif
                    <form method="POST" action="{{ route('admin.current-account.stripe-sync') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="currency" value="{{ $selectedCurrency }}">
                        <button type="submit" class="btn btn-xs btn-outline-primary px-2 py-1">
                            <i class="mdi mdi-refresh me-1"></i>Sync Stripe Fees
                        </button>
                    </form>
                </div>
            </div>

            @if(!empty($stripeSum['by_settlement_currency']))
                <div class="row g-2 mb-3">
                    @foreach($stripeSum['by_settlement_currency'] as $sc)
                        <div class="col-12 col-md-4">
                            <div class="p-3 rounded-3 h-100" style="background-color: #EFF6FF; border: 1px solid #BFDBFE;">
                                <div class="small fw-semibold text-primary">Gross Stripe Payments ({{ $sc['currency'] }})</div>
                                <div class="fs-5 ca-money text-dark mt-1">{{ $currencyService->formatMinor($sc['gross_minor'], $sc['currency']) }}</div>
                                <div class="ca-muted" style="font-size: 0.72rem;">{{ $sc['reconciled_count'] + $sc['pending_count'] }} payment(s)</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-3 rounded-3 h-100" style="background-color: #FEF2F2; border: 1px solid #FECACA;">
                                <div class="small fw-semibold text-danger">Actual Stripe Processing Fees ({{ $sc['currency'] }})</div>
                                <div class="fs-5 ca-money text-danger mt-1">
                                    @if($sc['reconciled_count'] > 0)
                                        -{{ $currencyService->formatMinor($sc['fee_minor'], $sc['currency']) }}
                                    @else
                                        Fee Pending Reconciliation
                                    @endif
                                </div>
                                <div class="ca-muted" style="font-size: 0.72rem;">
                                    {{ $sc['pending_count'] > 0 ? $sc['pending_count'] . ' pending reconciliation' : 'All fees reconciled from Stripe' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-3 rounded-3 h-100" style="background-color: #ECFDF5; border: 1px solid #A7F3D0;">
                                <div class="small fw-semibold text-success">Net Stripe Amount ({{ $sc['currency'] }})</div>
                                <div class="fs-5 ca-money text-success mt-1">
                                    @if($sc['reconciled_count'] > 0)
                                        {{ $currencyService->formatMinor($sc['net_minor'], $sc['currency']) }}
                                    @else
                                        Pending Reconciliation
                                    @endif
                                </div>
                                <div class="ca-muted" style="font-size: 0.72rem;">Gross minus actual Stripe fee</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if(!empty($stripeSum['transactions']))
            <div class="ca-table-scroll">
                <table class="table table-hover ca-table" style="min-width: 760px;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Booking</th>
                            <th>Payment Intent</th>
                            <th class="text-end">Gross Amount</th>
                            <th class="text-end">Stripe Fee</th>
                            <th class="text-end">Net Amount</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stripeSum['transactions'] as $stx)
                            <tr>
                                <td class="ca-muted text-nowrap">{{ $stx['date'] }}</td>
                                <td class="text-nowrap">
                                    @if($stx['booking_id'])
                                        <a href="{{ route('admin.bookings.show', $stx['booking_id']) }}" class="text-decoration-none fw-semibold">{{ $stx['booking_reference'] }}</a>
                                    @else
                                        {{ $stx['booking_reference'] }}
                                    @endif
                                </td>
                                <td><span class="d-inline-block text-truncate align-middle" style="max-width: 150px;" title="{{ $stx['reference'] }}">{{ $stx['reference'] }}</span></td>
                                <td class="text-end ca-money text-dark text-nowrap">{{ $stx['gross_formatted'] }}</td>
                                <td class="text-end ca-money text-nowrap {{ $stx['is_reconciled'] ? 'text-danger' : 'text-warning-emphasis' }}">
                                    {{ $stx['is_reconciled'] ? '-' . $stx['fee_formatted'] : 'Fee Pending Reconciliation' }}
                                </td>
                                <td class="text-end ca-money text-nowrap {{ $stx['is_reconciled'] ? 'text-success' : 'ca-muted' }}">
                                    {{ $stx['is_reconciled'] ? $stx['net_formatted'] : 'Pending' }}
                                </td>
                                <td class="fw-semibold text-nowrap">{{ $stx['settlement_currency'] }}</td>
                                <td class="text-nowrap"><span class="badge {{ $stx['status_badge'] }}">{{ $stx['status'] }}</span></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-xs btn-outline-primary px-2 py-1 js-view-tx-detail" data-url="{{ route('admin.current-account.detail', $stx['uid']) }}">
                                        <i class="mdi mdi-eye me-1"></i>Details
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-3 pb-3 small ca-muted">No confirmed Stripe payments recorded in {{ $period['month_label'] }}.</div>
        @endif
    </div>

    <!-- SECTION 4: FULL-WIDTH MONTHLY TRANSACTION HISTORY -->
    <div class="ca-card mb-3" id="txLedgerCard">
        <div class="p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="ca-section-title mb-0"><i class="mdi mdi-format-list-bulleted me-1 text-primary"></i>Transaction History &mdash; {{ $period['month_label'] }} ({{ $selectedCurrency }})</h6>
                    <div class="ca-muted" style="font-size: 0.75rem;">Unified read-only ledger across verified receipts, commissions, payables, earnings, and payouts.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="caTxLoadingBar" class="small text-primary d-none"><span class="spinner-border spinner-border-sm me-1"></span>Loading...</span>
                    <span id="caTxCountBadge" class="badge bg-light text-dark border">{{ $unifiedTransactions->total() }} records</span>
                </div>
            </div>

            <div id="caTxErrorBar" class="alert alert-danger py-2 px-3 small d-none mb-2">Unable to load transactions. Please try again.</div>

            <!-- Compact AJAX Filter Rows -->
            <form method="GET" action="{{ route('admin.current-account.index') }}" id="caTxFilterForm" class="row g-2 align-items-end">
                <input type="hidden" name="currency" value="{{ $selectedCurrency }}">
                <input type="hidden" name="month" value="{{ $period['month'] }}">
                <input type="hidden" name="sort_by" id="caSortByInput" value="{{ request('sort_by', 'occurred_at') }}">
                <input type="hidden" name="sort_dir" id="caSortDirInput" value="{{ request('sort_dir', 'desc') }}">

                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white ca-filter-control"><i class="mdi mdi-magnify"></i></span>
                        <input type="text" name="search" id="caSearchInput" value="{{ request('search') }}" class="form-control ca-filter-control" placeholder="Search ref, booking, customer, store, DP...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="transaction_type" class="form-select form-select-sm ca-filter-control js-ca-filter-select">
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $key => $label)
                            <option value="{{ $key }}" @selected(request('transaction_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="classification" class="form-select form-select-sm ca-filter-control js-ca-filter-select">
                        <option value="">All Classifications</option>
                        @foreach($classifications as $cls)
                            <option value="{{ $cls }}" @selected(request('classification') === $cls)>{{ $cls }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="store_id" class="form-select form-select-sm ca-filter-control js-ca-filter-select">
                        <option value="">All Stores</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" @selected((string) request('store_id') === (string) $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="delivery_person_id" class="form-select form-select-sm ca-filter-control js-ca-filter-select">
                        <option value="">All Delivery Persons</option>
                        @foreach($deliveryPersons as $dp)
                            <option value="{{ $dp->id }}" @selected((string) request('delivery_person_id') === (string) $dp->id)>{{ $dp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-1">
                    <button type="submit" id="caTxFilterSubmitBtn" class="btn btn-sm fw-bold w-100 ca-filter-control" style="background-color: #FFC629; color: #171717;">
                        <i class="mdi mdi-filter"></i> Filter
                    </button>
                </div>

                <div class="col-6 col-md-2">
                    <select name="payment_method" class="form-select form-select-sm ca-filter-control js-ca-filter-select">
                        <option value="">All Methods</option>
                        <option value="stripe" @selected(request('payment_method') === 'stripe')>Stripe</option>
                        <option value="paypal" @selected(request('payment_method') === 'paypal')>PayPal</option>
                        <option value="cash_in_hand" @selected(request('payment_method') === 'cash_in_hand')>Cash in Hand</option>
                        <option value="online_transfer" @selected(request('payment_method') === 'online_transfer')>Online Transfer</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm ca-filter-control js-ca-filter-select">
                        <option value="">All Statuses</option>
                        <option value="Confirmed" @selected(request('status') === 'Confirmed')>Confirmed</option>
                        <option value="Finalized" @selected(request('status') === 'Finalized')>Finalized</option>
                        <option value="Payable" @selected(request('status') === 'Payable')>Payable</option>
                        <option value="Paid" @selected(request('status') === 'Paid')>Paid</option>
                        <option value="Settled" @selected(request('status') === 'Settled')>Settled</option>
                        <option value="Refunded" @selected(request('status') === 'Refunded')>Refunded</option>
                        <option value="Credited to DP Ledger" @selected(request('status') === 'Credited to DP Ledger')>Credited to DP Ledger</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="button" id="caTxResetBtn" class="btn btn-sm btn-light border w-100 ca-filter-control d-inline-flex align-items-center justify-content-center">Reset Filters</button>
                </div>
            </form>
        </div>

        <div class="ca-table-scroll">
            <table class="table table-hover ca-table">
                <thead>
                    <tr>
                        <th><a href="#" class="text-decoration-none text-reset js-ca-sort" data-sort="occurred_at">Date <i class="mdi mdi-sort"></i></a></th>
                        <th>Reference</th>
                        <th>Booking</th>
                        <th><a href="#" class="text-decoration-none text-reset js-ca-sort" data-sort="transaction_type">Transaction Type <i class="mdi mdi-sort"></i></a></th>
                        <th><a href="#" class="text-decoration-none text-reset js-ca-sort" data-sort="counterparty">Counterparty <i class="mdi mdi-sort"></i></a></th>
                        <th>Description</th>
                        <th class="text-end">Money In</th>
                        <th class="text-end">Money Out</th>
                        <th class="text-end"><a href="#" class="text-decoration-none text-reset js-ca-sort" data-sort="amount">Accounting <i class="mdi mdi-sort"></i></a></th>
                        <th>Currency</th>
                        <th><a href="#" class="text-decoration-none text-reset js-ca-sort" data-sort="status">Status <i class="mdi mdi-sort"></i></a></th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="caTxTbody">
                    @include('admin::accounts._transactions_rows', ['unifiedTransactions' => $unifiedTransactions, 'currencyService' => $currencyService, 'period' => $period])
                </tbody>
            </table>
        </div>

        <div id="caTxPagination" class="{{ $unifiedTransactions->hasPages() ? 'p-3 border-top' : '' }}">
            @if($unifiedTransactions->hasPages())
                {{ $unifiedTransactions->links() }}
            @endif
        </div>
    </div>

    <!-- SECTION 5: ADMIN PLATFORM LEDGER (ADJUSTMENTS & REVERSALS) -->
    <div class="ca-card">
        <div class="p-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="ca-section-title mb-0">Admin Platform Ledger (Delivery Margin &amp; Adjustments)</h6>
                <div class="ca-muted" style="font-size: 0.75rem;">Platform Ledger Balance: <strong class="text-dark">{{ $currencyService->formatMinor((int) $activeSummary['current_balance_minor'], $selectedCurrency) }}</strong></div>
            </div>
            <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#platformLedgerCollapse">Toggle Ledger</button>
        </div>
        <div class="collapse" id="platformLedgerCollapse">
            <div class="ca-table-scroll">
                <table class="table table-hover ca-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Booking</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance After</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td class="fw-semibold text-nowrap">{{ $tx->reference }}</td>
                                <td class="ca-muted text-nowrap">{{ $tx->created_at?->format('d M Y, H:i') }}</td>
                                <td class="text-nowrap">{{ ucfirst(str_replace('_', ' ', (string) $tx->transaction_type)) }}</td>
                                <td class="text-nowrap">{{ $tx->booking?->reference ?? '-' }}</td>
                                <td>{{ $tx->description }}</td>
                                <td class="text-end ca-money text-nowrap {{ $tx->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->direction === 'credit' ? '+' : '-' }}{{ $currencyService->formatMinor(\App\Services\StoreAccountService::decimalToMinor($tx->amount), $tx->currency) }}
                                </td>
                                <td class="text-end ca-money text-nowrap">{{ $currencyService->formatMinor(\App\Services\StoreAccountService::decimalToMinor($tx->balance_after), $tx->currency) }}</td>
                                <td class="text-end text-nowrap">
                                    @can('adjust-admin-accounts', 'admin')
                                        @if($tx->transaction_type !== \App\Models\AdminAccountTransaction::TYPE_REVERSAL && !in_array($tx->id, $reversedTransactionIds, true))
                                            <button type="button"
                                                    class="btn btn-xs btn-outline-danger js-reverse-tx-btn"
                                                    data-action="{{ route('admin.current-account.reverse', $tx) }}"
                                                    data-ref="{{ $tx->reference }}">
                                                <i class="mdi mdi-undo me-1"></i>Reverse
                                            </button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center ca-muted py-3">No platform ledger entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- AJAX Transaction Detail Modal -->
<div class="modal fade" id="txDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-2" style="background-color: #FFF8E5; border-radius: 16px 16px 0 0;">
                <div>
                    <h6 class="modal-title fw-bold mb-0" style="color: #171717;">Transaction Details</h6>
                    <div class="small ca-muted" id="txDetailRefSubtitle">Loading...</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body small" id="txDetailModalBody">
                <div class="text-center py-4 ca-muted">
                    <div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>
                    Loading transaction details...
                </div>
            </div>
            <div class="modal-footer border-0 pt-1">
                <a href="#" id="txDetailBookingLink" class="btn btn-sm fw-bold d-none" style="background-color: #FFC629; color: #171717;">Open Booking</a>
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@can('adjust-admin-accounts', 'admin')
<!-- Manual Adjustment Modal -->
<div class="modal fade" id="adminAdjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form method="POST" action="{{ route('admin.current-account.adjust') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Manual Admin Account Adjustment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold mb-1">Currency</label>
                            <select name="currency" class="form-select form-select-sm" required>
                                @foreach($availableCurrencies as $cur)
                                    <option value="{{ $cur }}" {{ $cur === $selectedCurrency ? 'selected' : '' }}>{{ $cur }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold mb-1">Direction</label>
                            <select name="direction" class="form-select form-select-sm" required>
                                <option value="credit">Credit (+)</option>
                                <option value="debit">Debit (-)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold mb-1">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required placeholder="0.00">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold mb-1">Booking Reference (Optional)</label>
                        <input type="text" name="booking_reference" class="form-control form-control-sm" placeholder="e.g. LND-2026-0001">
                    </div>
                    <div>
                        <label class="form-label fw-semibold mb-1">Description / Reason <span class="text-danger">*</span></label>
                        <textarea name="description" rows="2" class="form-control form-control-sm" required placeholder="Reason for manual adjustment"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm fw-bold" style="background-color: #FFC629; color: #171717;">Record Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reversal Modal -->
<div class="modal fade" id="adminReversalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form method="POST" id="adminReversalForm" action="#">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Compensating Reversal (<span id="reverseTxRefLabel"></span>)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small">
                    <p class="ca-muted mb-2">Ledger entries are immutable. This creates an equal and opposite reversal entry.</p>
                    <label class="form-label fw-semibold mb-1">Reversal Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="2" class="form-control form-control-sm" required placeholder="Explain why this entry is being reversed"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirm Reversal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const trendEl = document.getElementById('caMonthlyTrendChart');
        if (trendEl && typeof ApexCharts !== 'undefined') {
            const trendData = @json($monthlyTrend);
            const chart = new ApexCharts(trendEl, {
                chart: { type: 'bar', height: 210, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [
                    { name: 'Customer Receipts', data: trendData.receipts || [] },
                    { name: 'Cash Outflows', data: trendData.outflows || [] },
                    { name: 'Store Commission', data: trendData.commissions || [] }
                ],
                colors: ['#16A34A', '#EF4444', '#8B5CF6'],
                plotOptions: { bar: { columnWidth: '58%', borderRadius: 4 } },
                dataLabels: { enabled: false },
                xaxis: { categories: trendData.labels || [], labels: { style: { fontSize: '11px', colors: '#475569' } } },
                yaxis: { labels: { style: { fontSize: '11px', colors: '#475569' } } },
                legend: { position: 'top', fontSize: '11px', labels: { colors: '#171717' } },
                grid: { borderColor: '#F1F5F9' },
                tooltip: { y: { formatter: val => '{{ $selectedCurrency }} ' + Number(val).toFixed(2) } }
            });
            chart.render();
        }

        // AJAX Transaction History Filtering, Debounced Search, Pagination, and Sorting
        const txForm = document.getElementById('caTxFilterForm');
        const txTbody = document.getElementById('caTxTbody');
        const txCountBadge = document.getElementById('caTxCountBadge');
        const txPagination = document.getElementById('caTxPagination');
        const txLoadingBar = document.getElementById('caTxLoadingBar');
        const txErrorBar = document.getElementById('caTxErrorBar');
        const txEndpoint = @json(route('admin.current-account.transactions'));
        let activeController = null;
        let searchDebounceTimer = null;

        async function loadTransactionsAjax(page = 1) {
            if (!txForm || !txTbody) return;

            if (activeController) {
                activeController.abort();
            }
            activeController = new AbortController();

            const params = new URLSearchParams(new FormData(txForm));
            params.set('page', String(page));

            txErrorBar?.classList.add('d-none');
            txLoadingBar?.classList.remove('d-none');

            try {
                const res = await fetch(`${txEndpoint}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: activeController.signal,
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Unable to load transactions.');
                }

                txTbody.innerHTML = data.rows_html || '';
                if (txCountBadge) {
                    txCountBadge.textContent = `${data.total} records`;
                }
                if (txPagination) {
                    txPagination.innerHTML = data.pagination_html || '';
                    txPagination.className = data.pagination_html ? 'p-3 border-top' : '';
                }

                // Update browser URL cleanly without full-page reload
                const cleanUrl = `${window.location.pathname}?${params.toString()}`;
                window.history.replaceState({}, '', cleanUrl);
            } catch (err) {
                if (err.name === 'AbortError') return;
                txErrorBar?.classList.remove('d-none');
            } finally {
                txLoadingBar?.classList.add('d-none');
            }
        }

        if (txForm) {
            txForm.addEventListener('submit', function (e) {
                e.preventDefault();
                loadTransactionsAjax(1);
            });

            txForm.querySelectorAll('.js-ca-filter-select').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    loadTransactionsAjax(1);
                });
            });

            const searchInput = document.getElementById('caSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(function () {
                        loadTransactionsAjax(1);
                    }, 350);
                });
            }

            const resetBtn = document.getElementById('caTxResetBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    if (searchInput) searchInput.value = '';
                    txForm.querySelectorAll('.js-ca-filter-select').forEach(function (sel) {
                        sel.value = '';
                        if (window.jQuery && jQuery(sel).hasClass('select2-hidden-accessible')) {
                            jQuery(sel).val('').trigger('change.select2');
                        }
                    });
                    document.getElementById('caSortByInput').value = 'occurred_at';
                    document.getElementById('caSortDirInput').value = 'desc';
                    loadTransactionsAjax(1);
                });
            }
        }

        // Delegated AJAX Pagination
        document.addEventListener('click', function (e) {
            const link = e.target.closest('#caTxPagination a.page-link');
            if (!link) return;
            e.preventDefault();
            const urlObj = new URL(link.href, window.location.origin);
            const page = parseInt(urlObj.searchParams.get('page') || '1', 10);
            loadTransactionsAjax(page);
        });

        // Delegated AJAX Sorting
        document.addEventListener('click', function (e) {
            const sortLink = e.target.closest('.js-ca-sort');
            if (!sortLink) return;
            e.preventDefault();
            const col = sortLink.getAttribute('data-sort');
            const sortByInput = document.getElementById('caSortByInput');
            const sortDirInput = document.getElementById('caSortDirInput');
            if (!sortByInput || !sortDirInput) return;

            if (sortByInput.value === col) {
                sortDirInput.value = sortDirInput.value === 'desc' ? 'asc' : 'desc';
            } else {
                sortByInput.value = col;
                sortDirInput.value = 'desc';
            }
            loadTransactionsAjax(1);
        });
    });

    $(document).on('click', '.js-reverse-tx-btn', function () {
        $('#adminReversalForm').attr('action', $(this).data('action'));
        $('#reverseTxRefLabel').text($(this).data('ref'));
        const el = document.getElementById('adminReversalModal');
        if (el && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    });

    $(document).on('click', '.js-view-tx-detail', async function () {
        const url = $(this).data('url');
        const modalEl = document.getElementById('txDetailModal');
        const body = document.getElementById('txDetailModalBody');
        const sub = document.getElementById('txDetailRefSubtitle');
        const bookingLink = document.getElementById('txDetailBookingLink');

        sub.textContent = 'Loading...';
        body.innerHTML = '<div class="text-center py-4 ca-muted"><div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>Loading transaction details...</div>';
        bookingLink.classList.add('d-none');

        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Unable to load transactions. Please try again.');
            }
            const tx = data.transaction;
            sub.textContent = `${tx.reference} • ${tx.classification}`;
            if (tx.booking_url) {
                bookingLink.href = tx.booking_url;
                bookingLink.classList.remove('d-none');
            }

            const fields = [
                ['Transaction Reference', tx.reference],
                ['Transaction Type', tx.transaction_type_label],
                ['Classification', tx.classification],
                ['Status', tx.status],
                ['Counterparty', tx.counterparty],
                ['Related Booking', tx.booking_reference || '-'],
                ['Date & Time', tx.occurred_at],
                ['Currency', tx.currency],
                ['Money In', tx.money_in_minor > 0 ? tx.money_in_formatted : '-'],
                ['Money Out', tx.money_out_minor > 0 ? tx.money_out_formatted : '-'],
                ['Accounting Amount', tx.accounting_amount_formatted],
            ];

            const payload = tx.detail_payload || {};
            Object.entries(payload).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== '') {
                    const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                    fields.push([label, String(v)]);
                }
            });

            const gridItems = fields.map(([label, val]) => `
                <div class="col-12 col-md-6">
                    <div class="p-2 rounded-3 bg-light border h-100">
                        <div class="ca-muted" style="font-size: 0.72rem;">${label}</div>
                        <div class="fw-semibold text-dark text-break">${val}</div>
                    </div>
                </div>
            `).join('');

            body.innerHTML = `
                <div class="row g-2 mb-2">${gridItems}</div>
                <div class="p-2 rounded-3 bg-light border">
                    <div class="ca-muted" style="font-size: 0.72rem;">Description</div>
                    <div class="text-dark">${tx.description}</div>
                </div>
            `;
        } catch (err) {
            body.innerHTML = `<div class="alert alert-danger py-2 mb-0">Unable to load transactions. Please try again.</div>`;
        }
    });
</script>
@endpush
