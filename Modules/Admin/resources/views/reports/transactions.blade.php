@extends('admin::layouts.app')

@section('title', 'Transactions & Finance - Reports')

@section('content')
<div class="container-fluid py-3">
    @include('admin::reports.partials.header', [
        'reportTitle' => 'Transactions & Finance',
        'reportSubtitle' => 'Track verified customer payments, gateway fees, platform commission, store share, delivery person earnings, and COD custody.',
    ])

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">Verified Receipts</div><div class="fs-5 fw-bold text-success">{{ $finance['kpis']['verified_receipts_formatted'] }}</div><div class="small text-muted">Stripe Fees: {{ $finance['kpis']['stripe_fee_formatted'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">Platform Commission</div><div class="fs-5 fw-bold text-dark">{{ $finance['kpis']['platform_commission_formatted'] }}</div><div class="small text-muted">Store Share: {{ $finance['kpis']['store_share_formatted'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">DP Earnings</div><div class="fs-5 fw-bold text-primary">{{ $finance['kpis']['dp_earnings_formatted'] }}</div><div class="small text-muted">Credited in period</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">COD Cash Custody</div><div class="fs-5 fw-bold" style="color:#6D28D9;">{{ $finance['kpis']['cod_custody_formatted'] }}</div><div class="small text-muted">Current DP balance</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold small d-flex justify-content-between align-items-center">
            <span>Customer Payment Transactions ({{ $currency }})</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0" style="min-width: 720px;">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Booking Ref</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Gateway Fee</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $p)
                            <tr>
                                <td>#{{ $p->id }}</td>
                                <td>
                                    @if($p->booking)
                                        <a href="{{ route('admin.bookings.show', $p->booking) }}" class="fw-semibold text-decoration-none">{{ $p->booking->reference }}</a>
                                    @else
                                        #{{ $p->booking_id }}
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ strtoupper((string) $p->payment_method) }}</span></td>
                                <td><span class="badge bg-{{ in_array($p->status, ['confirmed', 'verified', 'paid', 'succeeded'], true) ? 'success' : 'secondary' }}">{{ ucfirst($p->status) }}</span></td>
                                <td class="fw-semibold">{{ $currencyService->formatMinor((int) $p->amount_minor, $p->currency) }}</td>
                                <td>{{ $p->gateway_fee_minor !== null ? $currencyService->formatMinor((int) $p->gateway_fee_minor, $p->currency) : '—' }}</td>
                                <td class="text-nowrap">{{ ($p->verified_at ?? $p->created_at)?->format('d M Y, H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No transactions found for the selected period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
            <div class="card-footer bg-white">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection
