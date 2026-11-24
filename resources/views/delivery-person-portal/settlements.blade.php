@extends('delivery-person-portal.layouts.app')
@section('title', 'Admin Settlements')
@section('page_title', 'My Admin Cash Settlements')

@section('content')
<div class="dp-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Channel</th>
                    <th>Admin</th>
                    <th class="text-end">Amount</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settlements as $s)
                    <tr>
                        <td>{{ $s->settled_at?->format('d M Y, H:i') }}</td>
                        <td class="fw-bold">{{ $s->settlement_reference }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $s->settlement_channel)) }}</span></td>
                        <td>{{ $s->admin?->name ?? 'Admin' }}</td>
                        <td class="text-end fw-bold text-success">{{ $currencyService->formatMinor(\App\Services\StoreAccountService::decimalToMinor($s->amount), $s->currency) }}</td>
                        <td>{{ $s->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No settlements recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($settlements->hasPages())
        <div class="p-3 border-top">{{ $settlements->links() }}</div>
    @endif
</div>
@endsection
