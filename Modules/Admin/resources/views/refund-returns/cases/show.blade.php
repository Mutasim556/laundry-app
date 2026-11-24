@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 100%; overflow-x: clip;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('admin.refund-returns.cases.index') }}" class="small text-decoration-none">&larr; Back to All Requests</a>
            <h4 class="fw-bold mb-0 mt-1" style="color: #171717;">Case {{ $claim->case_reference }} &mdash; {{ $claim->type_label }}</h4>
        </div>
        <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ $claim->status_label }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3 mb-3">
        <!-- Case Summary -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold mb-2">Claim Details &amp; Evidence</h6>
                    <div class="mb-1"><strong>Booking:</strong> <a href="{{ route('admin.bookings.show', $claim->booking) }}">{{ $claim->booking->reference }}</a> ({{ $claim->booking->customer_name }})</div>
                    <div class="mb-1"><strong>Store:</strong> {{ $claim->store?->name ?? 'Unassigned' }}</div>
                    <div class="mb-1"><strong>Policy Version:</strong> {{ $claim->policyVersion ? 'v' . $claim->policyVersion->version_number : 'Default Rules' }}</div>
                    <div class="mb-1"><strong>Reason:</strong> {{ ucfirst(str_replace('_', ' ', $claim->reason_code)) }}</div>
                    <div class="mb-2"><strong>Description:</strong> {{ $claim->description }}</div>

                    @if($claim->items->isNotEmpty())
                        <div class="fw-semibold mb-1">Affected Items:</div>
                        <ul class="ps-3 mb-2">
                            @foreach($claim->items as $ci)
                                <li>{{ $ci->service_name_snapshot }} &times; {{ $ci->affected_quantity }} ({{ $currencyService->formatMinor((int) $ci->claimed_amount_minor, $claim->currency) }})</li>
                            @endforeach
                        </ul>
                    @endif

                    @if($claim->evidence->isNotEmpty())
                        <div class="fw-semibold mb-1">Supporting Evidence:</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($claim->evidence as $ev)
                                <a href="{{ route('orders.claims.evidence', $ev) }}" class="btn btn-xs btn-outline-dark">{{ $ev->original_name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Refund Calculation Preview & Approval -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background-color: #FFF8E5; border: 1px solid #FDE68A !important;">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold mb-2">Refund Calculation Preview</h6>
                    <div class="d-flex justify-content-between mb-1"><span>Confirmed Paid:</span><strong>{{ $currencyService->formatMinor($refundPreview['confirmed_paid_minor'], $refundPreview['currency']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span>Already Refunded:</span><strong class="text-danger">{{ $currencyService->formatMinor($refundPreview['already_refunded_minor'], $refundPreview['currency']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span>In-Flight Refunds:</span><strong>{{ $currencyService->formatMinor($refundPreview['in_flight_refund_minor'], $refundPreview['currency']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2 pt-1 border-top"><span class="fw-bold">Remaining Refundable Ceiling:</span><strong class="text-success">{{ $currencyService->formatMinor($refundPreview['remaining_refundable_minor'], $refundPreview['currency']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-3"><span>Policy Stage Guidance ({{ $refundPreview['stage_refund_percentage'] }}%):</span><strong>{{ $currencyService->formatMinor($refundPreview['policy_suggested_minor'], $refundPreview['currency']) }}</strong></div>

                    @if($refundPreview['remaining_refundable_minor'] > 0)
                        <form method="POST" action="{{ route('admin.refund-returns.cases.refunds.store', $claim) }}" class="row g-2 align-items-end border-top pt-2">
                            @csrf
                            <div class="col-4">
                                <label class="form-label fw-semibold mb-1">Amount ({{ $refundPreview['currency'] }})</label>
                                <input type="number" step="0.01" min="0.01" max="{{ number_format($refundPreview['remaining_refundable_minor'] / 100, 2, '.', '') }}" name="amount" class="form-control form-control-sm" value="{{ number_format(($refundPreview['item_level_suggested_minor'] ?: $refundPreview['policy_suggested_minor']) / 100, 2, '.', '') }}" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold mb-1">Method</label>
                                <select name="payment_method" class="form-select form-select-sm" required>
                                    @php $gw = strtolower((string) $claim->booking->payment_gateway); @endphp
                                    <option value="stripe" @selected($gw === 'stripe')>Stripe</option>
                                    <option value="paypal" @selected($gw === 'paypal')>PayPal</option>
                                    <option value="cod_manual" @selected($gw === 'cod')>COD / Manual Payout</option>
                                    <option value="online_transfer">Online Transfer</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-sm fw-bold w-100" style="background-color: #FFC629; color: #171717;">Approve Refund</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- Case Status Update -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold mb-2">Update Case Status</h6>
                    <form method="POST" action="{{ route('admin.refund-returns.cases.status', $claim) }}">
                        @csrf
                        <div class="mb-2">
                            <select name="status" class="form-select form-select-sm" required>
                                @foreach(\App\Models\CustomerServiceClaim::ALLOWED_TRANSITIONS[$claim->status] ?? [] as $next)
                                    <option value="{{ $next }}">{{ ucfirst(str_replace('_', ' ', $next)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea name="decision_notes" rows="2" class="form-control form-control-sm" placeholder="Decision / rejection reason..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-dark w-100">Update Status</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rewash Logistics -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold mb-2">Rewash / Return Logistics</h6>
                    <form method="POST" action="{{ route('admin.refund-returns.cases.rewash', $claim) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label mb-1">Collection Delivery Person</label>
                            <select name="collection_delivery_person_id" class="form-select form-select-sm">
                                <option value="">Unassigned</option>
                                @foreach($deliveryPersons as $dp)
                                    <option value="{{ $dp->id }}">{{ $dp->full_name }} ({{ $dp->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-1">Return Delivery Person</label>
                            <select name="delivery_delivery_person_id" class="form-select form-select-sm">
                                <option value="">Unassigned</option>
                                @foreach($deliveryPersons as $dp)
                                    <option value="{{ $dp->id }}">{{ $dp->full_name }} ({{ $dp->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Schedule Rewash Jobs</button>
                    </form>
                    @if($claim->deliveryJobs->isNotEmpty())
                        <div class="mt-2 pt-2 border-top">
                            @foreach($claim->deliveryJobs as $dj)
                                <div><strong>{{ $dj->job_reference }}</strong> ({{ ucfirst(str_replace('_', ' ', $dj->job_type)) }}) &mdash; {{ ucfirst($dj->status) }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Store / DP Responsibility -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold mb-2">Responsibility &amp; Ledger Adjustment</h6>
                    <form method="POST" action="{{ route('admin.refund-returns.cases.liability', $claim) }}">
                        @csrf
                        <div class="mb-2">
                            <select name="liability_party" class="form-select form-select-sm" required>
                                <option value="none" @selected($claim->liability_party === 'none')>No External Liability</option>
                                <option value="platform" @selected($claim->liability_party === 'platform')>Platform Absorbed</option>
                                <option value="store" @selected($claim->liability_party === 'store')>Store Responsible</option>
                                <option value="delivery_person" @selected($claim->liability_party === 'delivery_person')>Delivery Person Responsible</option>
                                <option value="shared" @selected($claim->liability_party === 'shared')>Shared</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="number" step="0.01" min="0" name="store_liability" class="form-control form-control-sm" placeholder="Store ({{ $claim->currency }})"></div>
                            <div class="col-6"><input type="number" step="0.01" min="0" name="dp_liability" class="form-control form-control-sm" placeholder="DP ({{ $claim->currency }})"></div>
                        </div>
                        <div class="mb-2">
                            <select name="delivery_person_id" class="form-select form-select-sm">
                                <option value="">DP (if DP liable)</option>
                                @foreach($deliveryPersons as $dp)
                                    <option value="{{ $dp->id }}">{{ $dp->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2"><input type="text" name="reason" class="form-control form-control-sm" required placeholder="Required reason..."></div>
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Record Responsibility</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Refund Transactions for This Case -->
    @if($claim->refundTransactions->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
            <div class="card-body p-3 small">
                <h6 class="fw-bold mb-2">Refund Transactions</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Ref</th><th>Method</th><th>Amount</th><th>Status</th><th>Provider Ref</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            @foreach($claim->refundTransactions as $rt)
                                <tr>
                                    <td class="fw-bold">{{ $rt->refund_reference }}</td>
                                    <td>{{ strtoupper($rt->payment_method) }}</td>
                                    <td class="fw-bold">{{ $currencyService->formatMinor((int) $rt->amount_minor, $rt->currency) }}</td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis">{{ ucfirst(str_replace('_', ' ', $rt->status)) }}</span></td>
                                    <td>{{ $rt->provider_refund_id ?: ($rt->external_reference ?: '-') }}</td>
                                    <td class="text-end">
                                        @if(in_array($rt->payment_method, ['stripe', 'paypal'], true) && !$rt->isCompletedDisbursement())
                                            <form method="POST" action="{{ route('admin.refund-returns.refunds.execute', $rt) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-primary">Execute {{ ucfirst($rt->payment_method) }} Refund</button>
                                            </form>
                                        @elseif($rt->payment_method === 'cod_manual' && !$rt->isCompletedDisbursement())
                                            <form method="POST" action="{{ route('admin.refund-returns.refunds.cod-paid', $rt) }}" class="d-inline-flex gap-1">
                                                @csrf
                                                <input type="text" name="payout_recipient" class="form-control form-control-sm" style="width:120px;" required placeholder="Recipient">
                                                <input type="text" name="payout_method" class="form-control form-control-sm" style="width:100px;" required placeholder="Bank/Cash">
                                                <button type="submit" class="btn btn-xs btn-success">Mark Paid</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
