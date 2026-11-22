@extends('admin::layouts.app')

@section('content')
@php
    $canManage = auth('admin')->user()?->can('manage-payment-gateways') ?? false;
    $stripe = $gateways->get('stripe');
    $paypal = $gateways->get('paypal');
    $cod = $gateways->get('cod');
@endphp

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-4 rounded-4 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <div>
            <h2 class="mb-1 fw-bold text-white">Payment Gateway Management</h2>
            <p class="mb-0 text-white-50 small">Configure, activate, test, and order the 3 supported booking payment gateways: Stripe, PayPal, and Cash on Delivery.</p>
        </div>
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold">
            3 Fixed Gateways Supported
        </span>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
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
        @foreach ([$stripe, $paypal, $cod] as $gateway)
            @continue(! $gateway)
            @php
                $isConfigured = $gateway->isConfigured();
                $masked = $gateway->getMaskedCredentials();
                $statusBadgeClass = $gateway->is_active ? 'bg-success' : ($isConfigured ? 'bg-secondary' : 'bg-warning text-dark');
                $statusLabel = $gateway->is_active ? 'Active' : ($isConfigured ? 'Inactive (Configured)' : 'Unconfigured');
            @endphp
            <div class="col-12 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4">
                    <div class="card-body d-flex flex-column p-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <span class="badge bg-light text-dark border text-uppercase mb-2">{{ $gateway->gateway_code }}</span>
                                <h5 class="card-title fw-bold mb-1">{{ $gateway->display_name }}</h5>
                            </div>
                            <span class="badge {{ $statusBadgeClass }} rounded-pill px-3 py-2">{{ $statusLabel }}</span>
                        </div>

                        <p class="text-muted small mb-3">{{ $gateway->description }}</p>

                        <div class="bg-light rounded-3 p-3 mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Environment:</span>
                                <strong class="text-uppercase">{{ $gateway->environment === 'none' ? 'N/A' : $gateway->environment }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Display Order:</span>
                                <strong>#{{ $gateway->sort_order }}</strong>
                            </div>
                            @if ($gateway->gateway_code === 'stripe')
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Publishable Key:</span>
                                    <strong>{{ ! empty($gateway->getPublicValue('publishable_key')) ? 'Configured' : 'Missing' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Secret Key:</span>
                                    <strong>{{ ($masked['has_secret_key'] ?? false) ? '********' : 'Missing' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Currency:</span>
                                    <strong>{{ $gateway->getPublicValue('currency', 'GBP') }}</strong>
                                </div>
                            @elseif ($gateway->gateway_code === 'paypal')
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Client ID:</span>
                                    <strong>{{ ! empty($gateway->getPublicValue('client_id')) ? 'Configured' : 'Missing' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Client Secret:</span>
                                    <strong>{{ ($masked['has_client_secret'] ?? false) ? '********' : 'Missing' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Currency:</span>
                                    <strong>{{ $gateway->getPublicValue('currency', 'GBP') }}</strong>
                                </div>
                            @else
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Credentials Required:</span>
                                    <strong>No</strong>
                                </div>
                            @endif
                        </div>

                        @if ($gateway->last_tested_at)
                            <div class="small mb-3 {{ $gateway->last_test_status === 'success' ? 'text-success' : 'text-danger' }}">
                                <strong>Last Test ({{ $gateway->last_tested_at->diffForHumans() }}):</strong>
                                {{ $gateway->last_test_message }}
                            </div>
                        @endif

                        <div class="mt-auto d-flex flex-wrap gap-2">
                            @if ($canManage)
                                <button type="button"
                                    class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalGateway{{ ucfirst($gateway->gateway_code) }}">
                                    <i class="mdi mdi-cog-outline me-1"></i> Configure
                                </button>

                                <form method="POST"
                                    action="{{ route('admin.payment-gateways.toggle', $gateway->gateway_code) }}"
                                    @if ($gateway->is_active) onsubmit="return confirm('Deactivate {{ $gateway->display_name }}? New bookings will no longer see this payment method, while historical bookings remain intact.');" @endif>
                                    @csrf
                                    <input type="hidden" name="is_active" value="{{ $gateway->is_active ? '0' : '1' }}">
                                    <button type="submit"
                                        class="btn btn-sm {{ $gateway->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        {{ (! $gateway->is_active && ! $isConfigured) ? 'disabled' : '' }}>
                                        {{ $gateway->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>

                                @if ($gateway->gateway_code !== 'cod')
                                    <form method="POST" action="{{ route('admin.payment-gateways.test', $gateway->gateway_code) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" {{ ! $isConfigured ? 'disabled' : '' }}>
                                            Test Connection
                                        </button>
                                    </form>
                                @endif

                                @if ($isConfigured && $gateway->gateway_code !== 'cod')
                                    <form method="POST"
                                        action="{{ route('admin.payment-gateways.remove', $gateway->gateway_code) }}"
                                        onsubmit="return confirm('Remove stored credentials and deactivate {{ $gateway->display_name }}? Historical booking records will remain intact.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            Remove Config
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="text-muted small">Read-only access (manage-payment-gateways required).</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="mb-0 fw-bold">Recent Gateway Configuration Audit Log</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>Gateway</th>
                            <th>Action</th>
                            <th>Admin</th>
                            <th class="pe-4">Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td class="ps-4 small text-muted">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $log->gateway_code }}</span></td>
                                <td><span class="badge bg-info-subtle text-info-emphasis text-uppercase">{{ $log->action }}</span></td>
                                <td class="small">{{ $log->admin_name ?? 'System' }}</td>
                                <td class="pe-4 small">{{ $log->summary }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">No gateway configuration changes recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if ($canManage && $stripe)
@php $stripeMasked = $stripe->getMaskedCredentials(); @endphp
<div class="modal fade" id="modalGatewayStripe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.payment-gateways.stripe.update') }}" class="modal-content rounded-4 border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Configure Stripe Gateway</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Display Name</label>
                        <input type="text" name="display_name" class="form-control" value="{{ old('display_name', $stripe->display_name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mode</label>
                        <select name="environment" class="form-select">
                            <option value="sandbox" {{ $stripe->environment === 'sandbox' ? 'selected' : '' }}>Sandbox (Test)</option>
                            <option value="live" {{ $stripe->environment === 'live' ? 'selected' : '' }}>Live</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Currency</label>
                        <input type="text" name="currency" class="form-control text-uppercase" maxlength="3" value="{{ $stripe->getPublicValue('currency', 'GBP') }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Customer Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $stripe->description }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Publishable Key</label>
                        <input type="text" name="publishable_key" class="form-control" placeholder="pk_test_... or pk_live_..." value="{{ $stripe->getPublicValue('publishable_key', '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Secret Key</label>
                        <input type="password" name="secret_key" class="form-control" autocomplete="new-password"
                            placeholder="{{ ($stripeMasked['has_secret_key'] ?? false) ? '******** (leave blank to keep existing)' : 'sk_test_... or sk_live_...' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Webhook Signing Secret</label>
                        <input type="password" name="webhook_secret" class="form-control" autocomplete="new-password"
                            placeholder="{{ ($stripeMasked['has_webhook_secret'] ?? false) ? '******** (leave blank to keep existing)' : 'whsec_...' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Capture Mode</label>
                        <select name="capture_mode" class="form-select">
                            <option value="delayed_setup_intent" {{ $stripe->getPublicValue('capture_mode', 'delayed_setup_intent') === 'delayed_setup_intent' ? 'selected' : '' }}>
                                Delayed Off-Session Charge (SetupIntent)
                            </option>
                            <option value="immediate" {{ $stripe->getPublicValue('capture_mode') === 'immediate' ? 'selected' : '' }}>
                                Immediate Capture
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Display Order</label>
                        <input type="number" name="sort_order" class="form-control" min="0" max="999" value="{{ $stripe->sort_order }}" required>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-4 pt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="enable_saved_cards" value="1" id="stripeSavedCards"
                                {{ $stripe->getPublicValue('enable_saved_cards', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="stripeSavedCards">Enable Saved Cards</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="stripeActive"
                                {{ $stripe->is_active ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="stripeActive">Active on Customer Checkout</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Stripe Settings</button>
            </div>
        </form>
    </div>
</div>
@endif

@if ($canManage && $paypal)
@php $paypalMasked = $paypal->getMaskedCredentials(); @endphp
<div class="modal fade" id="modalGatewayPaypal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.payment-gateways.paypal.update') }}" class="modal-content rounded-4 border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Configure PayPal Gateway</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Display Name</label>
                        <input type="text" name="display_name" class="form-control" value="{{ $paypal->display_name }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mode</label>
                        <select name="environment" class="form-select">
                            <option value="sandbox" {{ $paypal->environment === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="live" {{ $paypal->environment === 'live' ? 'selected' : '' }}>Live</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Currency</label>
                        <input type="text" name="currency" class="form-control text-uppercase" maxlength="3" value="{{ $paypal->getPublicValue('currency', 'GBP') }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Customer Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $paypal->description }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">PayPal Client ID</label>
                        <input type="text" name="client_id" class="form-control" placeholder="PayPal REST App Client ID" value="{{ $paypal->getPublicValue('client_id', '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">PayPal Client Secret</label>
                        <input type="password" name="client_secret" class="form-control" autocomplete="new-password"
                            placeholder="{{ ($paypalMasked['has_client_secret'] ?? false) ? '******** (leave blank to keep existing)' : 'Enter PayPal Client Secret' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Webhook ID (Optional)</label>
                        <input type="text" name="webhook_id" class="form-control" placeholder="PayPal Webhook ID" value="{{ $paypal->getPublicValue('webhook_id', '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Display Order</label>
                        <input type="number" name="sort_order" class="form-control" min="0" max="999" value="{{ $paypal->sort_order }}" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end pb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="paypalActive"
                                {{ $paypal->is_active ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="paypalActive">Active on Customer Checkout</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save PayPal Settings</button>
            </div>
        </form>
    </div>
</div>
@endif

@if ($canManage && $cod)
<div class="modal fade" id="modalGatewayCod" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.payment-gateways.cod.update') }}" class="modal-content rounded-4 border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Configure Cash on Delivery (COD)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Display Name</label>
                        <input type="text" name="display_name" class="form-control" value="{{ $cod->display_name }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Customer Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $cod->description }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Delivery Instructions</label>
                        <input type="text" name="instructions" class="form-control" value="{{ $cod->getPublicValue('instructions', '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Display Order</label>
                        <input type="number" name="sort_order" class="form-control" min="0" max="999" value="{{ $cod->sort_order }}" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end pb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="codActive"
                                {{ $cod->is_active ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="codActive">Active on Customer Checkout</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save COD Settings</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
