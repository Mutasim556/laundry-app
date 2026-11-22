@extends('admin::layouts.app')

@section('content')
@php
    $canManage = auth('admin')->user()?->can('manage-booking-fees') ?? false;
    $activeCurrency = currencyCode();
@endphp

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-4 rounded-4 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <div>
            <h2 class="mb-1 fw-bold text-white">Booking Fee Settings</h2>
            <p class="mb-0 text-white-50 small">Control Service Fee, Collection &amp; Delivery Fee, Free Delivery, and Minimum Order Free-Delivery Thresholds for Step 5.</p>
        </div>
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold">
            Confirmed Historical Bookings Preserved
        </span>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please review the validation errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- SERVICE FEE CARD -->
        <div class="col-12 col-lg-6">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">Service Fee</h5>
                        <small class="text-muted">Applies to new bookings on Step 5 — Review &amp; Confirm</small>
                    </div>
                    <span class="badge {{ $serviceFee->is_active ? 'bg-success' : 'bg-secondary' }} rounded-pill px-3 py-2">
                        {{ $serviceFee->is_active ? 'Enabled' : 'Disabled (' . formatMoney(0) . ')' }}
                    </span>
                </div>
                <form method="POST" action="{{ route('admin.booking-fees.service-fee.update') }}">
                    @csrf
                    <div class="card-body p-4">
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="serviceFeeActive"
                                {{ old('is_active', $serviceFee->is_active) ? 'checked' : '' }} {{ !$canManage ? 'disabled' : '' }}>
                            <label class="form-check-label fw-semibold" for="serviceFeeActive">Enable Service Fee</label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Display Name</label>
                                <input type="text" name="display_name" class="form-control"
                                    value="{{ old('display_name', $serviceFee->display_name) }}" {{ !$canManage ? 'readonly' : '' }} required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fee Type</label>
                                <select name="fee_type" class="form-select" {{ !$canManage ? 'disabled' : '' }}>
                                    <option value="fixed" {{ old('fee_type', $serviceFee->fee_type) === 'fixed' ? 'selected' : '' }}>Fixed Amount ({{ $activeCurrency }})</option>
                                    <option value="percentage" {{ old('fee_type', $serviceFee->fee_type) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fee Value</label>
                                <input type="number" step="0.01" min="0" name="fee_value" class="form-control"
                                    value="{{ old('fee_value', number_format((float) $serviceFee->fee_value, 2, '.', '')) }}" {{ !$canManage ? 'readonly' : '' }} required>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="small text-muted pb-2">
                                    Current effective: <strong>{{ $serviceFee->is_active ? ($serviceFee->fee_type === 'percentage' ? number_format((float) $serviceFee->fee_value, 2) . '%' : formatMoney($serviceFee->fee_value)) : formatMoney(0) }}</strong>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="2" {{ !$canManage ? 'readonly' : '' }}>{{ old('description', $serviceFee->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                    @if ($canManage)
                        <div class="card-footer bg-white border-top px-4 py-3 text-end">
                            <button type="submit" class="btn btn-primary px-4">Save Service Fee</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- COLLECTION & DELIVERY FEE CARD -->
        <div class="col-12 col-lg-6">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">Collection &amp; Delivery</h5>
                        <small class="text-muted">Doorstep pickup &amp; return fee rules</small>
                    </div>
                    @if (!$deliveryFee->is_active || $deliveryFee->is_free)
                        <span class="badge bg-info text-dark rounded-pill px-3 py-2">FREE</span>
                    @else
                        <span class="badge bg-success rounded-pill px-3 py-2">{{ formatMoney($deliveryFee->fee_value) }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.booking-fees.delivery-fee.update') }}">
                    @csrf
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap gap-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="deliveryFeeActive"
                                    {{ old('is_active', $deliveryFee->is_active) ? 'checked' : '' }} {{ !$canManage ? 'disabled' : '' }}>
                                <label class="form-check-label fw-semibold" for="deliveryFeeActive">Enable Collection &amp; Delivery Fee</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_free" value="1" id="deliveryFeeFree"
                                    {{ old('is_free', $deliveryFee->is_free) ? 'checked' : '' }} {{ !$canManage ? 'disabled' : '' }}>
                                <label class="form-check-label fw-semibold text-success" for="deliveryFeeFree">Free Collection &amp; Delivery</label>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Display Name</label>
                                <input type="text" name="display_name" class="form-control"
                                    value="{{ old('display_name', $deliveryFee->display_name) }}" {{ !$canManage ? 'readonly' : '' }} required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fee Amount ({{ $activeCurrency }})</label>
                                <input type="number" step="0.01" min="0" name="fee_value" class="form-control"
                                    value="{{ old('fee_value', number_format((float) $deliveryFee->fee_value, 2, '.', '')) }}" {{ !$canManage ? 'readonly' : '' }} required>
                            </div>

                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="threshold_enabled" value="1" id="deliveryThresholdEnabled"
                                            {{ old('threshold_enabled', $deliveryFee->threshold_enabled) ? 'checked' : '' }} {{ !$canManage ? 'disabled' : '' }}>
                                        <label class="form-check-label fw-semibold" for="deliveryThresholdEnabled">Free Collection &amp; Delivery Above Minimum Order</label>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Minimum Confirmed Order Amount ({{ $activeCurrency }})</label>
                                            <input type="number" step="0.01" min="0" name="threshold_amount" class="form-control form-control-sm"
                                                value="{{ old('threshold_amount', number_format($deliveryFee->threshold_amount, 2, '.', '')) }}" {{ !$canManage ? 'readonly' : '' }}>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Threshold Evaluation</label>
                                            <select name="threshold_after_discount" class="form-select form-select-sm" {{ !$canManage ? 'disabled' : '' }}>
                                                <option value="1" {{ old('threshold_after_discount', $deliveryFee->threshold_after_discount) ? 'selected' : '' }}>After Discounts</option>
                                                <option value="0" {{ !old('threshold_after_discount', $deliveryFee->threshold_after_discount) ? 'selected' : '' }}>Before Discounts</option>
                                            </select>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Evaluated only against confirmed order amounts (not unknown item-based or unweighed estimates).
                                    </small>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="2" {{ !$canManage ? 'readonly' : '' }}>{{ old('description', $deliveryFee->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                    @if ($canManage)
                        <div class="card-footer bg-white border-top px-4 py-3 text-end">
                            <button type="submit" class="btn btn-primary px-4">Save Collection &amp; Delivery</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="mb-0 fw-bold">Booking Fee Audit Log</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>Fee</th>
                            <th>Action</th>
                            <th>Admin</th>
                            <th class="pe-4">Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td class="ps-4 small text-muted">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $log->fee_code }}</span></td>
                                <td><span class="badge bg-info-subtle text-info-emphasis text-uppercase">{{ $log->action }}</span></td>
                                <td class="small">{{ $log->admin_name ?? 'System' }}</td>
                                <td class="pe-4 small">{{ $log->summary }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">No fee configuration changes recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
