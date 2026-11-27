@extends('admin::layouts.app')

@section('title', 'Customer Login OTP Settings - Website Manager')

@push('styles')
<style>
    .wm-otp-header {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%);
        border: 1px solid #FDE68A;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14);
    }
    .wm-otp-mode-card {
        border: 1.5px solid #E2E8F0;
        border-radius: 16px;
        padding: 1.1rem 1.2rem;
        cursor: pointer;
        transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
        height: 100%;
    }
    .wm-otp-mode-card:hover:not(.is-disabled) {
        border-color: #FCD34D;
        transform: translateY(-2px);
    }
    .wm-otp-mode-card.is-selected {
        border-color: #F59E0B;
        background: #FFFDF5;
        box-shadow: 0 8px 20px rgba(245, 158, 11, 0.15);
    }
    .wm-otp-mode-card.is-disabled {
        opacity: 0.62;
        cursor: not-allowed;
        background: #F8FAFC;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="wm-otp-header p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">Website Manager</span>
                </div>
                <h4 class="fw-bold mb-1">Customer Login OTP Settings</h4>
                <div class="small text-muted">Manage customer login verification during development and testing.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge rounded-pill bg-white text-dark border px-3 py-2 fw-semibold">
                    Environment: {{ $environmentLabel }}
                </span>
                <span id="wmOtpEffectiveBadge" class="badge rounded-pill px-3 py-2 fw-semibold {{ $effectiveMode === 'testing' ? 'bg-warning text-dark' : 'bg-success-subtle text-success border border-success-subtle' }}">
                    Effective OTP Mode: {{ $effectiveMode === 'testing' ? 'Testing OTP' : 'Email OTP' }}
                </span>
            </div>
        </div>
    </div>

    <div id="wmOtpAlertBox" class="alert d-none py-2 small mb-3"></div>

    @if (!$isTestingAuthorized)
        <div class="alert alert-info small rounded-4 border-0 shadow-sm mb-3">
            <strong>Testing OTP Mode Unavailable:</strong>
            Testing OTP Mode is disabled on this deployment.
            To authorize Testing OTP on a disposable local/testing environment, set <code>APP_ENV=local</code> (or <code>testing</code>) and <code>CUSTOMER_TEST_OTP_ALLOWED=true</code> in your local <code>.env</code>, then run <code>php artisan config:clear</code>.
            Never enable this on production.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <form id="wmCustomerOtpForm">
                    <div class="card-body p-3 p-md-4">
                        <h6 class="fw-bold mb-3">Select Customer Login Verification Mode</h6>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="wm-otp-mode-card d-block {{ $selectedMode === 'email' ? 'is-selected' : '' }}" data-mode="email">
                                    <div class="d-flex align-items-start gap-2">
                                        <input class="form-check-input mt-1 wm-otp-mode-radio" type="radio" name="mode" value="email" @checked($selectedMode === 'email')>
                                        <div>
                                            <div class="fw-bold text-dark">EMAIL OTP</div>
                                            <p class="small text-muted mb-0 mt-1">
                                                Send a unique verification code to the customer's email address.
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="wm-otp-mode-card d-block {{ $selectedMode === 'testing' ? 'is-selected' : '' }} {{ !$isTestingAuthorized ? 'is-disabled' : '' }}" data-mode="testing">
                                    <div class="d-flex align-items-start gap-2">
                                        <input class="form-check-input mt-1 wm-otp-mode-radio" type="radio" name="mode" value="testing" @checked($selectedMode === 'testing') @disabled(!$isTestingAuthorized)>
                                        <div>
                                            <div class="fw-bold text-dark">TESTING OTP</div>
                                            <p class="small text-muted mb-0 mt-1">
                                                Use a predefined OTP without sending an email. Available only in an authorized testing environment.
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div id="wmOtpTestingWarning" class="alert alert-warning small rounded-3 mb-0 {{ ($selectedMode === 'testing' && $isTestingAuthorized) ? '' : 'd-none' }}">
                            <strong>Testing Mode Active:</strong> Customers will not receive OTP emails.
                            @if ($isTestingAuthorized && $testOtpCode)
                                Predefined local testing OTP: <code class="fw-bold fs-6">{{ $testOtpCode }}</code>
                            @endif
                            <div class="mt-1 text-muted">Scope: Applies only to customer email login. Delivery Person, booking handover, Admin, Store, and payment flows are unaffected.</div>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-sm btn-warning fw-bold px-4" id="wmOtpSaveBtn">Save OTP Mode</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3 p-md-4 small">
                    <h6 class="fw-bold mb-2">Server-Side Gate Status</h6>
                    <ul class="list-unstyled mb-0 d-grid gap-2">
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">App Environment</span>
                            <span class="badge {{ $isEnvironmentApproved ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $environmentLabel }}</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">CUSTOMER_TEST_OTP_ALLOWED</span>
                            <span class="badge {{ $isTestingConfigEnabled ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $isTestingConfigEnabled ? 'true' : 'false' }}</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Testing Gate Authorized</span>
                            <span class="badge {{ $isTestingAuthorized ? 'bg-warning text-dark' : 'bg-secondary-subtle text-secondary' }}">{{ $isTestingAuthorized ? 'Yes' : 'No' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            @if($versions->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3 p-md-4 small">
                        <h6 class="fw-bold mb-2">Recent Mode Changes</h6>
                        <div class="d-grid gap-2">
                            @foreach($versions->take(5) as $ver)
                                <div class="p-2 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">{{ $ver->notes ?? 'Updated' }}</div>
                                        <div class="text-muted" style="font-size:0.74rem;">{{ $ver->createdByAdmin?->name ?? 'System' }}</div>
                                    </div>
                                    <span class="text-muted" style="font-size:0.74rem;">{{ $ver->created_at?->format('d M, H:i') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function($) {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function syncCards() {
        const val = $('input[name="mode"]:checked').val();
        $('.wm-otp-mode-card').removeClass('is-selected');
        $(`.wm-otp-mode-card[data-mode="${val}"]`).addClass('is-selected');
        if (val === 'testing') {
            $('#wmOtpTestingWarning').removeClass('d-none');
        } else {
            $('#wmOtpTestingWarning').addClass('d-none');
        }
    }

    $('.wm-otp-mode-radio').on('change', syncCards);

    $('#wmCustomerOtpForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#wmOtpSaveBtn').prop('disabled', true).text('Saving...');
        $('#wmOtpAlertBox').addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: @json(route('admin.website-manager.customer-login-otp.save')),
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            data: $(this).serialize(),
            success: function(res) {
                $('#wmOtpAlertBox').removeClass('d-none alert-danger').addClass('alert-success').text(res.message);
                const isTest = res.effective_mode === 'testing';
                $('#wmOtpEffectiveBadge')
                    .text('Effective OTP Mode: ' + (res.effective_mode_label || 'Email OTP'))
                    .removeClass('bg-warning text-dark bg-success-subtle text-success border border-success-subtle')
                    .addClass(isTest ? 'bg-warning text-dark' : 'bg-success-subtle text-success border border-success-subtle');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.errors?.mode?.[0] || xhr.responseJSON?.message || 'Failed to update OTP mode.';
                $('#wmOtpAlertBox').removeClass('d-none alert-success').addClass('alert-danger').text(msg);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Save OTP Mode');
            }
        });
    });
});
</script>
@endpush
