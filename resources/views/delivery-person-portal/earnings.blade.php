@extends('delivery-person-portal.layouts.app')
@section('title', 'My Earnings')
@section('page_title', 'Delivery Person Earnings')

@section('content')
<div class="dp-card p-3 mb-3" style="background: #ECFDF5; border-color: #A7F3D0;">
    <div class="small fw-semibold text-success">Delivery Person Earnings Balance (Personal Income)</div>
    <div class="fs-3 fw-bold text-success">{{ $currencyService->formatMinor($earningsBalanceMinor, $currency) }}</div>
    <div class="small text-muted">Separate from customer cash held in custody.</div>
</div>

<div class="dp-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Booking</th>
                    <th>Type</th>
                    <th class="text-end">Amount</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $e)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($e->occurred_at)->format('d M Y, H:i') }}</td>
                        <td class="fw-semibold">{{ $e->entry_reference }}</td>
                        <td>{{ $e->booking_reference ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $e->transaction_type)) }}</span></td>
                        <td class="text-end fw-bold {{ $e->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                            {{ $e->direction === 'credit' ? '+' : '-' }}{{ $currencyService->formatMinor((int) $e->amount_minor, $e->currency) }}
                        </td>
                        <td>{{ $e->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No earnings entries recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="p-3 border-top">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
