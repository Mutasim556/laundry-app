@extends('admin::layouts.app')

@section('title', 'Delivery Assignments - Admin Panel')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
@endpush

@section('content')
<div class="saas-page-shell py-4 px-3 px-md-4">
    <div class="saas-header-card">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h1 class="saas-header-title">Delivery Assignments</h1>
                <p class="saas-header-subtitle">Assign active couriers to booking collection and final delivery legs with postcode matching</p>
            </div>
            <div>
                <a href="{{ route('admin.delivery-persons.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold">Back to Couriers</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @can('manage-delivery-assignments', 'admin')
    <div class="saas-form-section mb-4">
        <div class="saas-section-heading">
            <span class="saas-section-badge"><i class="mdi mdi-truck-plus-outline"></i></span>
            <span>Assign Delivery Person to Booking</span>
        </div>
        <form method="POST" action="{{ route('admin.delivery-assignments.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Booking <span class="text-danger">*</span></label>
                <select name="booking_id" id="assign_booking_id" class="form-select" required>
                    <option value="">Select booking...</option>
                    @foreach($bookings as $bk)
                        <option value="{{ $bk->id }}">{{ $bk->reference }} — {{ $bk->customer_name }} ({{ \App\Models\Booking::STATUS_LABELS[$bk->booking_status] ?? $bk->booking_status }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Assignment Type <span class="text-danger">*</span></label>
                <select name="assignment_type" id="assign_type" class="form-select" required>
                    <option value="customer_collection">Customer Collection</option>
                    <option value="final_delivery">Final Delivery</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Store</label>
                <select name="store_id" id="assign_store_id" class="form-select">
                    <option value="">Select store...</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Delivery Person (Select2) <span class="text-danger">*</span></label>
                <select name="delivery_person_id" id="assign_person_select" class="form-select" required style="width: 100%;">
                    <option value="">Search eligible courier...</option>
                </select>
            </div>
            <div class="col-md-6" id="coverage_override_wrap" style="display: none;">
                <label class="form-label small fw-semibold text-warning-emphasis">Coverage Override Reason (Required when no postcode match)</label>
                <input type="text" name="coverage_override_reason" id="coverage_override_reason" class="form-control" placeholder="e.g. Nearest available courier approved by dispatch">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Rate Override (Optional)</label>
                <input type="number" step="0.01" min="0" name="override_rate" class="form-control" placeholder="Default rate">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Rate Override Reason</label>
                <input type="text" name="rate_override_reason" class="form-control" placeholder="Optional">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-saas-primary">Assign Courier</button>
            </div>
        </form>
    </div>
    @endcan

    <div class="saas-surface-card">
        <div class="table-responsive">
            <table class="table saas-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Assignment</th>
                        <th>Booking</th>
                        <th>Courier</th>
                        <th>Type</th>
                        <th>From &rarr; To</th>
                        <th>Rate / Earnings</th>
                        <th>COD</th>
                        <th>Status &amp; Transition</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $asg)
                        <tr>
                            <td><span class="saas-code-pill">{{ $asg->assignment_reference }}</span></td>
                            <td>
                                @if($asg->booking)
                                    <a href="{{ route('admin.bookings.show', $asg->booking) }}" class="fw-bold text-decoration-none">{{ $asg->booking->reference }}</a>
                                    <div class="small text-muted">{{ $asg->booking->customer_name }}</div>
                                @endif
                            </td>
                            <td>
                                @if($asg->deliveryPerson)
                                    <a href="{{ route('admin.delivery-persons.show', $asg->deliveryPerson) }}" class="fw-semibold text-dark text-decoration-none">{{ $asg->deliveryPerson->full_name }}</a>
                                    <div class="small text-muted">{{ $asg->matched_postcode ? 'Matched: ' . $asg->matched_postcode : 'Override' }}</div>
                                @endif
                            </td>
                            <td>{{ $asg->type_label }}</td>
                            <td class="small" style="max-width: 260px;">
                                <div class="text-truncate" title="{{ $asg->pickup_location }}"><strong>From:</strong> {{ $asg->pickup_location }}</div>
                                <div class="text-truncate" title="{{ $asg->destination_location }}"><strong>To:</strong> {{ $asg->destination_location }}</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $currencyService->formatMinor($asg->rate_minor, $asg->currency, true) }}</div>
                                <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $asg->earnings_status)) }}</span>
                            </td>
                            <td class="small">
                                @if($asg->cod_expected_minor > 0 || $asg->cod_collected_minor > 0)
                                    <div>Exp: {{ $currencyService->formatMinor($asg->cod_expected_minor, $asg->currency, true) }}</div>
                                    <div>Col: {{ $currencyService->formatMinor($asg->cod_collected_minor, $asg->currency, true) }}</div>
                                    <span class="badge bg-warning-subtle text-dark">{{ ucfirst(str_replace('_', ' ', $asg->cod_handover_status)) }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="saas-status-badge {{ in_array($asg->status, ['completed','handed_over']) ? 'active' : ($asg->status === 'cancelled' || $asg->status === 'failed' ? 'suspended' : 'pending') }}">{{ $asg->status_label }}</span>
                                @php $nexts = \Modules\Admin\Models\DeliveryAssignment::ALLOWED_TRANSITIONS[$asg->status] ?? []; @endphp
                                @if(!empty($nexts))
                                    <form method="POST" action="{{ route('admin.delivery-assignments.transition', $asg) }}" class="d-flex gap-1 mt-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="form-select form-select-sm" style="width: 145px;">
                                            @foreach($nexts as $nx)
                                                <option value="{{ $nx }}">{{ $statusLabels[$nx] ?? $nx }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-dark">Update</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">No delivery assignments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($assignments->hasPages())
            <div class="p-3 border-top">{{ $assignments->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const $sel = $('#assign_person_select');
            if ($sel.length && !$sel.hasClass('select2-hidden-accessible')) {
                $sel.select2({
                    placeholder: 'Search eligible delivery person...',
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 0,
                    ajax: {
                        url: '{{ route('admin.delivery-assignments.eligible-persons') }}',
                        dataType: 'json',
                        delay: 200,
                        data: params => ({
                            q: params.term || '',
                            booking_id: $('#assign_booking_id').val(),
                            assignment_type: $('#assign_type').val(),
                            store_id: $('#assign_store_id').val(),
                        }),
                        processResults: data => ({ results: data.results || [] })
                    }
                });

                $sel.on('select2:select', function(e) {
                    const item = e.params.data;
                    const needsOverride = item && item.postcode_matched === false;
                    $('#coverage_override_wrap').toggle(needsOverride);
                });
            }
        });
    </script>
@endpush
