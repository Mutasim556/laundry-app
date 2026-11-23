@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="mdi mdi-handshake-outline text-primary me-1"></i>Delivery Person Cash Settlements &amp; Transfers
            </h4>
            <div class="small text-muted">Collect daily Cash in Hand from Delivery Persons, verify online transfers, and inspect permanent settlement history.</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <!-- Record Daily Settlement Form -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Collect Daily Cash Handover</h6>
                    <form method="POST" action="{{ route('admin.delivery-person-settlements.store') }}" class="small">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label fw-semibold mb-1">Delivery Person</label>
                            <select name="delivery_person_id" class="form-select form-select-sm" required>
                                @foreach($people as $p)
                                    @php $bal = $personBalances[$p->id]['cash_in_hand_minor'] ?? 0; @endphp
                                    <option value="{{ $p->id }}" {{ ($selectedPerson?->id === $p->id) ? 'selected' : '' }}>
                                        {{ $p->full_name }} ({{ $p->code }}) — Cash in Hand: {{ $currencyService->formatMinor($bal, $p->currency ?: $currencyService->code()) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold mb-1">Amount Received</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold mb-1">Handover Method</label>
                                <select name="settlement_channel" class="form-select form-select-sm">
                                    <option value="cash_handover">Cash Handover</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-1">Notes</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional daily collection note">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Confirm Cash Receipt &amp; Settle</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pending Online Transfers -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2">Online Transfers Awaiting Admin Verification</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Booking</th>
                                    <th>Delivery Person</th>
                                    <th>Ref #</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingTransfers as $pt)
                                    <tr>
                                        <td>{{ $pt->booking?->reference }}</td>
                                        <td>{{ $pt->deliveryPerson?->full_name }}</td>
                                        <td><code>{{ $pt->external_reference }}</code></td>
                                        <td class="text-end fw-bold">{{ $currencyService->formatMinor((int) $pt->amount_minor, $pt->currency) }}</td>
                                        <td class="text-end text-nowrap">
                                            <form method="POST" action="{{ route('admin.booking-payments.verify', $pt) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="decision" value="approve">
                                                <button type="submit" class="btn btn-xs btn-success">Verify</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.booking-payments.verify', $pt) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="decision" value="reject">
                                                <button type="submit" class="btn btn-xs btn-outline-danger">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">No online transfers awaiting verification.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement History -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Settled At</th>
                            <th>Reference</th>
                            <th>Delivery Person</th>
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
                                <td>{{ $s->deliveryPerson?->full_name }} ({{ $s->deliveryPerson?->code }})</td>
                                <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $s->settlement_channel)) }}</span></td>
                                <td>{{ $s->admin?->name ?? 'Admin' }}</td>
                                <td class="text-end fw-bold text-success">{{ $currencyService->formatMinor(\App\Services\StoreAccountService::decimalToMinor($s->amount), $s->currency) }}</td>
                                <td>{{ $s->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No settlements recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($settlements->hasPages())
            <div class="card-footer bg-white border-top py-2 px-3">{{ $settlements->links() }}</div>
        @endif
    </div>
</div>
@endsection
