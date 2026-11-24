@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 100%; overflow-x: clip;">
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px; background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border: 1px solid #FDE68A !important;">
        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="fw-bold mb-0" style="color: #171717;"><i class="mdi mdi-undo me-1" style="color: #D97706;"></i>Refund, Rewash &amp; Claim Requests</h4>
                <div class="small text-muted">Manage customer cancellations, refunds, rewash corrections, and damage/missing item claims.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.refund-returns.policies.index') }}" class="btn btn-sm btn-light border"><i class="mdi mdi-settings me-1"></i>Policy Settings</a>
                <a href="{{ route('admin.refund-returns.refunds.index') }}" class="btn btn-sm fw-bold" style="background-color: #FFC629; color: #171717;">Refund Transactions</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search case ref, booking ref, customer...">
                </div>
                <div class="col-6 col-md-3">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Request Types</option>
                        <option value="cancellation_refund" @selected($activeType === 'cancellation_refund')>Cancellation &amp; Refund</option>
                        <option value="rewash" @selected($activeType === 'rewash')>Rewash / Correction</option>
                        <option value="damage_missing" @selected($activeType === 'damage_missing')>Damage / Missing Item</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach(['submitted','under_review','awaiting_evidence','approved','rejected','awaiting_refund_processing','refund_processing','refunded','rewash_in_progress','resolved','closed'] as $st)
                            <option value="{{ $st }}" @selected($activeStatus === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm fw-bold flex-grow-1" style="background-color: #FFC629; color: #171717;">Filter</button>
                    <a href="{{ route('admin.refund-returns.cases.index') }}" class="btn btn-sm btn-light border">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Case Ref</th>
                        <th>Booking</th>
                        <th>Customer</th>
                        <th>Store</th>
                        <th>Type</th>
                        <th>Policy</th>
                        <th class="text-end">Requested</th>
                        <th class="text-end">Approved</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                        <tr>
                            <td class="fw-bold">{{ $claim->case_reference }}</td>
                            <td>
                                @if($claim->booking)
                                    <a href="{{ route('admin.bookings.show', $claim->booking) }}" class="text-decoration-none fw-semibold">{{ $claim->booking->reference }}</a>
                                @endif
                            </td>
                            <td>{{ $claim->booking?->customer_name ?? '-' }}</td>
                            <td>{{ $claim->store?->name ?? '-' }}</td>
                            <td><span class="badge bg-info-subtle text-info-emphasis">{{ $claim->type_label }}</span></td>
                            <td>{{ $claim->policyVersion ? 'v' . $claim->policyVersion->version_number : 'Default' }}</td>
                            <td class="text-end">{{ $claim->requested_amount_minor > 0 ? $currencyService->formatMinor((int) $claim->requested_amount_minor, $claim->currency) : '-' }}</td>
                            <td class="text-end fw-bold text-success">{{ $claim->approved_amount_minor > 0 ? $currencyService->formatMinor((int) $claim->approved_amount_minor, $claim->currency) : '-' }}</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $claim->status_label }}</span></td>
                            <td class="text-muted text-nowrap">{{ $claim->created_at?->format('d M Y, H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.refund-returns.cases.show', $claim) }}" class="btn btn-xs btn-outline-primary px-2 py-1">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">No refund, rewash, or claim requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($claims->hasPages())
            <div class="card-footer bg-white border-0 py-2">{{ $claims->links() }}</div>
        @endif
    </div>
</div>
@endsection
