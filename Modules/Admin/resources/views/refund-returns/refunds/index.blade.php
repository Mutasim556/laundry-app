@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 100%; overflow-x: clip;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Refund Transactions</h4>
            <div class="small text-muted">Track Stripe, PayPal, and COD/manual customer refunds.</div>
        </div>
        <a href="{{ route('admin.refund-returns.cases.index') }}" class="btn btn-sm btn-light border">All Requests</a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Refund Ref</th>
                        <th>Case</th>
                        <th>Booking</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Provider / External Ref</th>
                        <th>Processed At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $r)
                        <tr>
                            <td class="fw-bold">{{ $r->refund_reference }}</td>
                            <td>@if($r->claim)<a href="{{ route('admin.refund-returns.cases.show', $r->claim) }}">{{ $r->claim->case_reference }}</a>@else - @endif</td>
                            <td>@if($r->booking)<a href="{{ route('admin.bookings.show', $r->booking) }}">{{ $r->booking->reference }}</a>@else - @endif</td>
                            <td>{{ strtoupper($r->payment_method) }}</td>
                            <td class="text-end fw-bold text-danger">{{ $currencyService->formatMinor((int) $r->amount_minor, $r->currency) }}</td>
                            <td><span class="badge bg-info-subtle text-info-emphasis">{{ ucfirst(str_replace('_', ' ', $r->status)) }}</span></td>
                            <td>{{ $r->provider_refund_id ?: ($r->external_reference ?: '-') }}</td>
                            <td class="text-muted">{{ $r->processed_at?->format('d M Y, H:i') ?? 'Pending' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No refund transactions recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($refunds->hasPages())
            <div class="card-footer bg-white border-0 py-2">{{ $refunds->links() }}</div>
        @endif
    </div>
</div>
@endsection
