@extends('delivery-person-portal.layouts.app')
@section('title', 'Cash Account')
@section('page_title', 'Customer Cash Custody Account')

@section('content')
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="dp-card p-3" style="background: #FFF1F2; border-color: #FECACA;">
            <div class="small fw-semibold text-danger">Cash in Hand</div>
            <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3" style="background: #F5F3FF; border-color: #DDD6FE;">
            <div class="small fw-semibold" style="color: #6D28D9;">Online Funds Held</div>
            <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['online_transfer_confirmed_minor'], $currency) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3" style="background: #FFF8E5; border-color: #FDE68A;">
            <div class="small fw-semibold text-warning-emphasis">Awaiting Settlement</div>
            <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3" style="background: #ECFDF5; border-color: #A7F3D0;">
            <div class="small fw-semibold text-success">Total Handed Over</div>
            <div class="fs-4 fw-bold text-success">{{ $currencyService->formatMinor($cashSummary['total_settled_minor'], $currency) }}</div>
        </div>
    </div>
</div>

<div class="dp-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end small">
        <div class="col-6 col-md-2">
            <label class="form-label mb-1">From Date</label>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1">To Date</label>
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Channel</label>
            <select name="payment_channel" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="cash_in_hand" {{ request('payment_channel') === 'cash_in_hand' ? 'selected' : '' }}>Cash in Hand</option>
                <option value="admin_settlement" {{ request('payment_channel') === 'admin_settlement' ? 'selected' : '' }}>Admin Settlement</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Booking Reference</label>
            <input type="text" name="booking_reference" value="{{ request('booking_reference') }}" class="form-control form-control-sm" placeholder="LND-...">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm dp-btn-yellow flex-grow-1">Filter</button>
            <a href="{{ route('delivery-person.cash-account') }}" class="btn btn-sm btn-light">Reset</a>
        </div>
    </form>
</div>

<div class="dp-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Booking</th>
                    <th>Channel</th>
                    <th class="text-end">Collected</th>
                    <th class="text-end">Settled</th>
                    <th class="text-end">Balance</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    @php
                        $amt = \App\Services\StoreAccountService::decimalToMinor($tx->amount);
                        $bal = \App\Services\StoreAccountService::decimalToMinor($tx->balance_after);
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i') }}</td>
                        <td>{{ $tx->booking?->reference ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $tx->payment_channel)) }}</span></td>
                        <td class="text-end text-success fw-semibold">{{ $tx->direction === 'credit' ? $currencyService->formatMinor($amt, $tx->currency) : '-' }}</td>
                        <td class="text-end text-danger fw-semibold">{{ $tx->direction === 'debit' ? $currencyService->formatMinor($amt, $tx->currency) : '-' }}</td>
                        <td class="text-end fw-bold">{{ $currencyService->formatMinor($bal, $tx->currency) }}</td>
                        <td>{{ $tx->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No cash custody transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="p-3 border-top">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
