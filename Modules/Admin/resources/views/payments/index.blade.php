@extends('admin::layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="p-4 rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <h2 class="mb-1 fw-bold text-white">Payments &amp; Bookings (Stripe)</h2>
        <p class="mb-0 text-white-50 small">Finalize weighed order amounts, initiate authorized Stripe off-session charges, retry failed payments, and issue refunds.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted text-uppercase fw-semibold">Pending Weighing</div><div class="fs-3 fw-bold">{{ $stats['pending'] }}</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted text-uppercase fw-semibold">Stripe Authorized</div><div class="fs-3 fw-bold text-primary">{{ $stats['authorized'] }}</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted text-uppercase fw-semibold">Paid</div><div class="fs-3 fw-bold text-success">{{ $stats['paid'] }}</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted text-uppercase fw-semibold">Failed / Action Needed</div><div class="fs-3 fw-bold text-danger">{{ $stats['failed'] }}</div></div></div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search reference, customer name, email...">
                </div>
                <div class="col-md-3">
                    <select name="payment_gateway" class="form-select">
                        <option value="">All Gateways</option>
                        <option value="stripe" @selected(request('payment_gateway') === 'stripe')>Stripe</option>
                        <option value="cod" @selected(request('payment_gateway') === 'cod')>Cash / Card on Delivery</option>
                        <option value="bkash" @selected(request('payment_gateway') === 'bkash')>bKash</option>
                        <option value="nagad" @selected(request('payment_gateway') === 'nagad')>Nagad</option>
                        <option value="bank_transfer" @selected(request('payment_gateway') === 'bank_transfer')>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="payment_status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach (['pending_item_count', 'authorized_for_later_charge', 'awaiting_final_amount', 'paid', 'failed', 'requires_action', 'refunded'] as $st)
                            <option value="{{ $st }}" @selected(request('payment_status') === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Gateway</th>
                            <th>Status</th>
                            <th>Final Amount</th>
                            <th>Stripe Refs</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $b)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $b->reference }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $b->customer_name }}</div>
                                    <small class="text-muted">{{ $b->customer_email }}</small>
                                </td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $b->payment_gateway }}</span></td>
                                <td>
                                    <span class="badge {{ $b->payment_status === 'paid' ? 'bg-success' : ($b->payment_status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                        {{ str_replace('_', ' ', $b->payment_status) }}
                                    </span>
                                    @if ($b->last_payment_error)
                                        <div class="small text-danger mt-1">{{ $b->last_payment_error }}</div>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $b->final_amount_formatted ?? 'Pending Weighing' }}</td>
                                <td class="small font-monospace">
                                    @if ($b->stripe_payment_intent_id)<div>PI: {{ $b->stripe_payment_intent_id }}</div>@endif
                                    @if ($b->stripe_setup_intent_id)<div>SI: {{ $b->stripe_setup_intent_id }}</div>@endif
                                    @if ($b->stripe_refund_id)<div>RF: {{ $b->stripe_refund_id }}</div>@endif
                                </td>
                                <td class="text-end">
                                    @can('manage-payments', 'admin')
                                        @if ($b->payment_status !== 'paid' && $b->payment_status !== 'refunded')
                                            <button type="button" class="btn btn-sm btn-primary js-finalize-charge"
                                                data-id="{{ $b->id }}"
                                                data-ref="{{ $b->reference }}"
                                                data-gateway="{{ $b->payment_gateway }}"
                                                data-amount="{{ $b->final_amount_minor ? number_format($b->final_amount_minor / 100, 2, '.', '') : '' }}">
                                                Set Amount / Charge
                                            </button>
                                        @endif
                                        @if ($b->payment_gateway === 'stripe' && in_array($b->payment_status, ['failed', 'requires_action'], true) && $b->final_amount_minor)
                                            <button type="button" class="btn btn-sm btn-warning js-retry-charge" data-id="{{ $b->id }}">Retry</button>
                                        @endif
                                    @endcan
                                    @can('refund-payments', 'admin')
                                        @if ($b->payment_gateway === 'stripe' && $b->payment_status === 'paid')
                                            <button type="button" class="btn btn-sm btn-outline-danger js-refund-booking" data-id="{{ $b->id }}" data-ref="{{ $b->reference }}">Refund</button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No bookings recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $bookings->links() }}
        </div>
    </div>
</div>

<!-- Finalize Amount & Charge Modal -->
<div class="modal fade" id="finalizeChargeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Finalize Amount — <span id="fc_ref"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="finalizeChargeForm">
                @csrf
                <input type="hidden" id="fc_booking_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="fc_amount" class="form-label fw-semibold small">Verified Final Order Amount</label>
                        <input type="number" step="0.01" min="0.50" class="form-control" id="fc_amount" name="final_amount" required>
                    </div>
                    <div class="form-check form-switch" id="fc_charge_wrap">
                        <input class="form-check-input" type="checkbox" id="fc_initiate_charge" name="initiate_charge" value="1">
                        <label class="form-check-label fw-semibold" for="fc_initiate_charge">Initiate off-session Stripe charge immediately</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Save &amp; Process</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

            $('.js-finalize-charge').on('click', function () {
                $('#fc_booking_id').val($(this).data('id'));
                $('#fc_ref').text($(this).data('ref'));
                $('#fc_amount').val($(this).data('amount'));
                const isStripe = $(this).data('gateway') === 'stripe';
                $('#fc_charge_wrap').toggleClass('d-none', !isStripe);
                $('#fc_initiate_charge').prop('checked', isStripe);
                $('#finalizeChargeModal').modal('show');
            });

            $('#finalizeChargeForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#fc_booking_id').val();
                $.ajax({
                    url: "{{ route('admin.payments.finalize', ':id') }}".replace(':id', id),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: (res) => { toastr.success(res.message); location.reload(); },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Payment action failed.')
                });
            });

            $('.js-retry-charge').on('click', function () {
                const id = $(this).data('id');
                $.ajax({
                    url: "{{ route('admin.payments.retry', ':id') }}".replace(':id', id),
                    method: 'POST',
                    success: (res) => { toastr.success(res.message); location.reload(); },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Retry failed.')
                });
            });

            $('.js-refund-booking').on('click', function () {
                const id = $(this).data('id');
                const ref = $(this).data('ref');
                Swal.fire({ title: `Refund ${ref}?`, icon: 'warning', showCancelButton: true }).then((r) => {
                    if (!r.isConfirmed) return;
                    $.ajax({
                        url: "{{ route('admin.payments.refund', ':id') }}".replace(':id', id),
                        method: 'POST',
                        success: (res) => { toastr.success(res.message); location.reload(); },
                        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Refund failed.')
                    });
                });
            });
        });
    </script>
@endpush
