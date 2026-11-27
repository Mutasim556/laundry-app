@extends('delivery-person-portal.layouts.app')
@section('title', 'My Profile & Bank Accounts')
@section('page_title', 'My Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
<style>
    .select2-container--default .select2-selection--single {
        height: 36px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 3px 6px;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 34px;
    }
    .dp-prof-card {
        border-radius: 18px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        animation: dpProfFadeUp 0.52s cubic-bezier(0.22, 1, 0.36, 1) both;
        transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.24s ease;
    }
    .dp-prof-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.07);
    }
    .dp-prof-avatar {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%);
        color: #171717;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 8px 18px rgba(245, 158, 11, 0.25);
        animation: dpProfFloat 3.4s ease-in-out infinite;
    }
    .dp-prof-info-pill {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 0.55rem 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .dp-prof-info-pill:hover {
        transform: translateX(3px);
        border-color: #FCD34D;
    }
    .dp-prof-pill-icon {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .dp-bank-item {
        border: 1.5px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.95rem 1.05rem;
        background: #FFFFFF;
        transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.22s ease, box-shadow 0.22s ease;
    }
    .dp-bank-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }
    .dp-bank-item.is-default {
        border-color: #FFC629;
        background: linear-gradient(180deg, #FFFDF5 0%, #FFFFFF 100%);
        box-shadow: 0 6px 16px rgba(255, 198, 41, 0.14);
    }
    @keyframes dpProfFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes dpProfFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }
    @media (prefers-reduced-motion: reduce) {
        .dp-prof-card, .dp-prof-avatar { animation: none !important; }
        .dp-prof-card, .dp-prof-info-pill, .dp-bank-item { transition: none !important; }
    }
</style>
@endpush

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="dp-card dp-prof-card p-3 p-md-4 h-100" style="border-top: 4px solid #FFC629; background: linear-gradient(180deg, #FFFFFF 0%, #FFFDF7 100%);">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="dp-prof-avatar" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <div>
                    <h6 class="fw-bold mb-0 fs-5">{{ $person->full_name }}</h6>
                    <span class="badge rounded-pill mt-1" style="background:#FFF8E5; color:#92400E; border:1px solid #FDE68A;">{{ $person->code }}</span>
                </div>
            </div>

            <div class="small mb-3 d-grid gap-2">
                <div class="dp-prof-info-pill">
                    <span class="dp-prof-pill-icon" style="background:#EFF6FF; color:#2563EB;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                    <div class="min-w-0"><div class="text-muted" style="font-size:0.7rem;">Email</div><div class="fw-semibold text-dark text-truncate">{{ $person->email }}</div></div>
                </div>
                <div class="dp-prof-info-pill">
                    <span class="dp-prof-pill-icon" style="background:#ECFDF5; color:#059669;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                    <div><div class="text-muted" style="font-size:0.7rem;">Phone</div><div class="fw-semibold text-dark">{{ $person->phone_display }}</div></div>
                </div>
                @php
                    $dpFullAddress = trim((string) ($person->full_residential_address ?: implode(', ', array_filter([
                        $person->address_line_1,
                        $person->address_line_2,
                        $person->city,
                        $person->residential_postcode,
                    ]))));
                @endphp
                <div class="dp-prof-info-pill">
                    <span class="dp-prof-pill-icon" style="background:#F5F3FF; color:#7C3AED;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                    <div><div class="text-muted" style="font-size:0.7rem;">Full Address</div><div class="fw-semibold text-dark">{{ $dpFullAddress !== '' ? $dpFullAddress : 'Not provided' }}</div></div>
                </div>
                <div class="dp-prof-info-pill">
                    <span class="dp-prof-pill-icon" style="background:#FFFBEB; color:#D97706;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                    <div><div class="text-muted" style="font-size:0.7rem;">Coverage Postcodes</div><div class="fw-semibold text-dark">{{ $person->postcodes->pluck('postcode')->implode(', ') ?: 'None' }}</div></div>
                </div>
            </div>

            <form method="POST" action="{{ route('delivery-person.profile.update') }}" class="d-flex align-items-end gap-2 pt-3 border-top">
                @csrf
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold mb-1">Availability Status</label>
                    <select name="availability_status" class="form-select form-select-sm">
                        <option value="available" {{ $person->availability_status === 'available' ? 'selected' : '' }}>Available</option>
                        <option value="busy" {{ $person->availability_status === 'busy' ? 'selected' : '' }}>Busy</option>
                        <option value="off_duty" {{ $person->availability_status === 'off_duty' ? 'selected' : '' }}>Off Duty</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm dp-btn-yellow px-3">Save</button>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="dp-card dp-prof-card p-3 p-md-4 h-100" style="border-top: 4px solid #3B82F6; animation-delay: 80ms;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="dp-prof-pill-icon" style="width:38px; height:38px; border-radius:11px; background:#EFF6FF; color:#2563EB;" aria-hidden="true">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h18"/><path d="M6 18v-7"/><path d="M10 18v-7"/><path d="M14 18v-7"/><path d="M18 18v-7"/><polygon points="12 2 20 7 4 7"/></svg>
                    </span>
                    <div>
                        <h6 class="fw-bold mb-0">My Bank Accounts</h6>
                        <div class="small text-muted">Save at least one bank account to request earnings withdrawals. Sensitive details are encrypted at rest.</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm dp-btn-yellow px-3 d-inline-flex align-items-center gap-1" id="dpOpenAddBankBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <span>Add Bank Account</span>
                </button>
            </div>

            <div id="dpBankAlertBox" class="d-none alert py-2 small mb-3"></div>

            <div id="dpBankAccountsList" class="d-grid gap-2">
                @forelse($bankAccounts as $acc)
                    <div class="dp-bank-item {{ $acc['is_default'] ? 'is-default' : '' }}" data-account-id="{{ $acc['id'] }}">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-bold small d-flex align-items-center gap-2">
                                    <span>{{ $acc['bank_name'] }}</span>
                                    <span class="badge bg-light text-dark border">{{ $acc['country_code'] }} / {{ $acc['currency'] }}</span>
                                    @if($acc['is_default'])
                                        <span class="badge rounded-pill" style="background:#FFC629;color:#171717;">Default</span>
                                    @endif
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ $acc['verification_label'] }}</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    Holder: <strong>{{ $acc['account_holder_name'] }}</strong> &middot; Account: <strong>{{ $acc['account_number_masked'] }}</strong>
                                    @if(!empty($acc['account_type']))
                                        &middot; {{ ucfirst($acc['account_type']) }}
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                @if(!$acc['is_default'])
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-sm py-1 px-2 dp-bank-default-btn" data-id="{{ $acc['id'] }}">Set Default</button>
                                @endif
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-1 px-2 dp-bank-edit-btn"
                                        data-account='@json($acc)'>Edit</button>
                                <button type="button" class="btn btn-xs btn-outline-danger btn-sm py-1 px-2 dp-bank-delete-btn" data-id="{{ $acc['id'] }}">Remove</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 border rounded-3 bg-light small" id="dpNoBankMsg">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width:42px; height:42px; background:#EFF6FF; color:#2563EB;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h18"/><path d="M6 18v-7"/><path d="M18 18v-7"/><polygon points="12 2 20 7 4 7"/></svg>
                        </div>
                        <div>No bank accounts saved yet. Click <strong>Add Bank Account</strong> to get started.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-3">
        <div class="dp-card p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width: 38px; height: 38px; background: #EDE9FE; color: #6D28D9; flex-shrink: 0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                    </span>
                    <div>
                        <h6 class="fw-bold mb-0" style="color: #171717;">Account Security</h6>
                        <div class="small text-muted">Protect your account</div>
                    </div>
                </div>
                <div class="p-2 rounded-3 bg-light border small mb-3">
                    <div class="text-muted" style="font-size: 0.75rem;">Password</div>
                    <div class="fw-bold text-dark">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                </div>
                <div id="dpProfilePwAlert" class="alert d-none py-2 small mb-3" role="status" aria-live="polite"></div>
            </div>
            <button type="button" id="dpOpenChangePasswordBtn" class="btn btn-sm btn-outline-dark w-100">Change Password</button>
        </div>
    </div>
</div>

<!-- Add / Edit Bank Account Modal -->
<div class="modal fade" id="dpBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom" style="background: #FFF8E5;">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="dpBankModalTitle">Add Bank Account</h6>
                    <div class="small text-muted">Details are encrypted and masked in ordinary views.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="dpBankForm" novalidate>
                <input type="hidden" id="dpBankId" value="">
                <div class="modal-body p-3 p-md-4">
                    <div id="dpBankModalAlert" class="alert alert-danger py-2 small d-none"></div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold mb-1">Country <span class="text-danger">*</span></label>
                            <select name="country_code" id="dpBankCountry" class="form-select form-select-sm" style="width:100%;">
                                @foreach($countriesMeta as $code => $meta)
                                    <option value="{{ $code }}" {{ ($person->country_code ?: 'GB') === $code ? 'selected' : '' }}>
                                        {{ $meta['name'] }} ({{ $code }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="country_code"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold mb-1">Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="dpBankCurrency" class="form-select form-select-sm">
                                @foreach($currencies as $curCode => $curMeta)
                                    <option value="{{ $curCode }}" {{ strtoupper($person->currency ?: 'GBP') === $curCode ? 'selected' : '' }}>
                                        {{ $curCode }} — {{ $curMeta['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="currency"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Account Holder Name <span class="text-danger">*</span></label>
                            <input type="text" name="account_holder_name" id="dpBankHolder" class="form-control form-control-sm" value="{{ $person->full_name }}" required>
                            <div class="invalid-feedback" data-field="account_holder_name"></div>
                        </div>
                        <div class="col-12 col-sm-7">
                            <label class="form-label small fw-semibold mb-1">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="dpBankName" class="form-control form-control-sm" placeholder="e.g. Barclays, HSBC, Chase" required>
                            <div class="invalid-feedback" data-field="bank_name"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label class="form-label small fw-semibold mb-1">Account Type</label>
                            <select name="account_type" id="dpBankType" class="form-select form-select-sm">
                                <option value="">Select (Optional)</option>
                                <option value="checking">Checking</option>
                                <option value="current">Current</option>
                                <option value="savings">Savings</option>
                                <option value="business">Business</option>
                            </select>
                            <div class="invalid-feedback" data-field="account_type"></div>
                        </div>
                        <div class="col-12 col-sm-7">
                            <label class="form-label small fw-semibold mb-1">Account Number <span class="text-danger" id="dpAccNumReq">*</span></label>
                            <input type="text" name="account_number" id="dpBankAccNum" class="form-control form-control-sm" autocomplete="off" placeholder="Enter account number">
                            <div class="form-text small" id="dpAccEditHint" style="display:none;">Leave blank when editing to keep existing encrypted account number.</div>
                            <div class="invalid-feedback" data-field="account_number"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label class="form-label small fw-semibold mb-1" id="dpRoutingLabel">Sort Code</label>
                            <input type="text" name="routing_code" id="dpBankRouting" class="form-control form-control-sm" autocomplete="off">
                            <div class="form-text small" id="dpRoutingHint"></div>
                            <div class="invalid-feedback" data-field="routing_code"></div>
                        </div>
                        <div class="col-12 col-sm-7" id="dpIbanGroup">
                            <label class="form-label small fw-semibold mb-1">IBAN (Optional)</label>
                            <input type="text" name="iban" id="dpBankIban" class="form-control form-control-sm" autocomplete="off">
                            <div class="invalid-feedback" data-field="iban"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label class="form-label small fw-semibold mb-1">SWIFT / BIC (Optional)</label>
                            <input type="text" name="swift_bic" id="dpBankSwift" class="form-control form-control-sm" autocomplete="off">
                            <div class="invalid-feedback" data-field="swift_bic"></div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="dpBankDefault">
                                <label class="form-check-label small fw-semibold" for="dpBankDefault">Set as default withdrawal bank account</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm dp-btn-yellow px-3" id="dpBankSubmitBtn">Save Bank Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Change Password Modal -->
<div class="modal fade" id="dpChangePasswordModal" tabindex="-1" aria-labelledby="dpChangePasswordTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 440px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom" style="background: #FFF8E5;">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="dpChangePasswordTitle">Change Password</h6>
                    <div class="small text-muted">Update your account password securely.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="dpChangePasswordForm" method="POST" action="{{ route('delivery-person.password.update') }}" novalidate>
                @csrf
                <div class="modal-body p-3 p-md-4">
                    <div id="dpPasswordModalAlert" class="alert alert-danger py-2 small d-none" role="alert"></div>

                    <div class="mb-3">
                        <label for="dpCurrentPassword" class="form-label small fw-semibold mb-1">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="dpCurrentPassword" name="current_password" class="form-control" autocomplete="current-password" required>
                            <button type="button" class="btn btn-outline-secondary js-dp-toggle-pw" data-target="dpCurrentPassword" aria-label="Show current password">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <div class="invalid-feedback" data-pw-field="current_password"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="dpNewPassword" class="form-label small fw-semibold mb-1">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="dpNewPassword" name="password" class="form-control" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary js-dp-toggle-pw" data-target="dpNewPassword" aria-label="Show new password">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <div class="invalid-feedback" data-pw-field="password"></div>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="dpConfirmPassword" class="form-label small fw-semibold mb-1">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="dpConfirmPassword" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary js-dp-toggle-pw" data-target="dpConfirmPassword" aria-label="Show confirm password">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <div class="invalid-feedback" data-pw-field="password_confirmation"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="dpPasswordSubmitBtn" class="btn btn-sm dp-btn-yellow px-3">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
(function($) {
    const countriesMeta = @json($countriesMeta);
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const storeUrl = @json(route('delivery-person.bank-accounts.store'));
    const baseUrl = @json(url('delivery-person/bank-accounts'));
    const bankModalEl = document.getElementById('dpBankModal');
    const bankModal = new bootstrap.Modal(bankModalEl);

    if ($.fn.select2) {
        $('#dpBankCountry').select2({
            dropdownParent: $('#dpBankModal'),
            width: '100%'
        });
    }

    function updateCountryFields(countryCode, autoSetCurrency = false) {
        const meta = countriesMeta[countryCode] || { routing_label: 'Routing / Branch Code', routing_required: false, routing_hint: '', iban_optional: true, currency: 'GBP' };
        $('#dpRoutingLabel').html(meta.routing_label + (meta.routing_required ? ' <span class="text-danger">*</span>' : ' (Optional)'));
        $('#dpRoutingHint').text(meta.routing_hint || '');
        $('#dpIbanGroup').toggle(!!meta.iban_optional);
        if (autoSetCurrency && meta.currency) {
            $('#dpBankCurrency').val(meta.currency);
        }
    }

    $('#dpBankCountry').on('change', function() {
        updateCountryFields($(this).val(), true);
    });

    function clearFormErrors() {
        $('#dpBankForm .is-invalid').removeClass('is-invalid');
        $('#dpBankForm .invalid-feedback').text('');
        $('#dpBankModalAlert').addClass('d-none').text('');
    }

    function showPageAlert(msg, type = 'success') {
        const box = $('#dpBankAlertBox');
        box.removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg);
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    function renderAccounts(accounts) {
        const list = $('#dpBankAccountsList');
        if (!accounts || !accounts.length) {
            list.html('<div class="text-center text-muted py-4 border rounded-3 bg-light small"><i class="mdi mdi-bank-off fs-3 d-block mb-1"></i>No bank accounts saved yet. Click <strong>Add Bank Account</strong> to get started.</div>');
            return;
        }
        let html = '';
        accounts.forEach(acc => {
            html += `
                <div class="dp-bank-item ${acc.is_default ? 'is-default' : ''}" data-account-id="${acc.id}">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-bold small d-flex align-items-center gap-2">
                                <span>${escapeHtml(acc.bank_name)}</span>
                                <span class="badge bg-light text-dark border">${escapeHtml(acc.country_code)} / ${escapeHtml(acc.currency)}</span>
                                ${acc.is_default ? '<span class="badge rounded-pill" style="background:#FFC629;color:#171717;">Default</span>' : ''}
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">${escapeHtml(acc.verification_label)}</span>
                            </div>
                            <div class="small text-muted mt-1">
                                Holder: <strong>${escapeHtml(acc.account_holder_name)}</strong> &middot; Account: <strong>${escapeHtml(acc.account_number_masked)}</strong>
                                ${acc.account_type ? ' &middot; ' + escapeHtml(acc.account_type.charAt(0).toUpperCase() + acc.account_type.slice(1)) : ''}
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            ${!acc.is_default ? `<button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 dp-bank-default-btn" data-id="${acc.id}">Set Default</button>` : ''}
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 dp-bank-edit-btn" data-account='${JSON.stringify(acc).replace(/'/g, "&#39;")}'>Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 dp-bank-delete-btn" data-id="${acc.id}">Remove</button>
                        </div>
                    </div>
                </div>
            `;
        });
        list.html(html);
    }

    $('#dpOpenAddBankBtn').on('click', function() {
        clearFormErrors();
        $('#dpBankId').val('');
        $('#dpBankModalTitle').text('Add Bank Account');
        $('#dpBankForm')[0].reset();
        $('#dpBankCountry').val(@json($person->country_code ?: 'GB')).trigger('change');
        $('#dpBankCurrency').val(@json(strtoupper($person->currency ?: 'GBP')));
        $('#dpBankHolder').val(@json($person->full_name));
        $('#dpAccEditHint').hide();
        $('#dpAccNumReq').show();
        bankModal.show();
    });

    $(document).on('click', '.dp-bank-edit-btn', function() {
        clearFormErrors();
        const acc = $(this).data('account');
        $('#dpBankId').val(acc.id);
        $('#dpBankModalTitle').text('Edit Bank Account (' + acc.account_number_masked + ')');
        $('#dpBankCountry').val(acc.country_code).trigger('change');
        $('#dpBankCurrency').val(acc.currency);
        $('#dpBankHolder').val(acc.account_holder_name);
        $('#dpBankName').val(acc.bank_name);
        $('#dpBankType').val(acc.account_type || '');
        $('#dpBankAccNum').val('');
        $('#dpBankRouting').val('');
        $('#dpBankIban').val('');
        $('#dpBankSwift').val(acc.swift_bic || '');
        $('#dpBankDefault').prop('checked', !!acc.is_default);
        $('#dpAccEditHint').show();
        $('#dpAccNumReq').hide();
        bankModal.show();
    });

    let submitting = false;
    $('#dpBankForm').on('submit', function(e) {
        e.preventDefault();
        if (submitting) return;
        submitting = true;
        clearFormErrors();
        const btn = $('#dpBankSubmitBtn').prop('disabled', true).text('Saving...');
        const id = $('#dpBankId').val();
        const url = id ? `${baseUrl}/${id}` : storeUrl;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function(res) {
                bankModal.hide();
                renderAccounts(res.accounts);
                showPageAlert(res.message || 'Bank account saved.', 'success');
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(field => {
                        const input = $(`#dpBankForm [name="${field}"]`);
                        input.addClass('is-invalid');
                        $(`#dpBankForm .invalid-feedback[data-field="${field}"]`).text(errs[field][0]);
                    });
                } else {
                    $('#dpBankModalAlert').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to save bank account.');
                }
            },
            complete: function() {
                submitting = false;
                btn.prop('disabled', false).text('Save Bank Account');
            }
        });
    });

    $(document).on('click', '.dp-bank-default-btn', function() {
        const id = $(this).data('id');
        $.ajax({
            url: `${baseUrl}/${id}/default`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function(res) {
                renderAccounts(res.accounts);
                showPageAlert(res.message, 'success');
            },
            error: function(xhr) {
                showPageAlert(xhr.responseJSON?.message || 'Failed to update default bank account.', 'danger');
            }
        });
    });

    $(document).on('click', '.dp-bank-delete-btn', function() {
        if (!confirm('Remove this bank account?')) return;
        const id = $(this).data('id');
        $.ajax({
            url: `${baseUrl}/${id}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function(res) {
                renderAccounts(res.accounts);
                showPageAlert(res.message, 'success');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.errors?.bank_account?.[0] || xhr.responseJSON?.message || 'Unable to remove bank account.';
                showPageAlert(msg, 'danger');
            }
        });
    });

    updateCountryFields($('#dpBankCountry').val(), false);

    // Change Password Modal & AJAX
    const pwModalEl = document.getElementById('dpChangePasswordModal');
    const pwModal = pwModalEl ? bootstrap.Modal.getOrCreateInstance(pwModalEl) : null;
    const pwForm = $('#dpChangePasswordForm');

    function resetPwModal() {
        if (!pwForm.length) return;
        pwForm[0].reset();
        pwForm.find('.is-invalid').removeClass('is-invalid');
        pwForm.find('.invalid-feedback').text('');
        $('#dpPasswordModalAlert').addClass('d-none').text('');
        pwForm.find('input[type="text"].form-control').attr('type', 'password');
    }

    $('#dpOpenChangePasswordBtn').on('click', function() {
        resetPwModal();
        pwModal?.show();
    });

    pwModalEl?.addEventListener('hidden.bs.modal', resetPwModal);

    $(document).on('click', '.js-dp-toggle-pw', function() {
        const input = document.getElementById($(this).data('target'));
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        $(this).attr('aria-label', isHidden ? 'Hide password' : 'Show password');
    });

    let pwSubmitting = false;
    pwForm.on('submit', function(e) {
        e.preventDefault();
        if (pwSubmitting) return;
        pwSubmitting = true;

        pwForm.find('.is-invalid').removeClass('is-invalid');
        pwForm.find('.invalid-feedback').text('');
        $('#dpPasswordModalAlert').addClass('d-none').text('');

        const btn = $('#dpPasswordSubmitBtn');
        const origText = btn.text();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Updating...');

        $.ajax({
            url: pwForm.attr('action'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: pwForm.serialize(),
            success: function(res) {
                pwModal?.hide();
                resetPwModal();
                $('#dpProfilePwAlert')
                    .removeClass('d-none alert-danger')
                    .addClass('alert-success')
                    .text(res.message || 'Your portal password has been updated.');
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(field => {
                        pwForm.find(`[name="${field}"]`).addClass('is-invalid');
                        pwForm.find(`.invalid-feedback[data-pw-field="${field}"]`).text(errs[field][0]);
                    });
                } else {
                    $('#dpPasswordModalAlert').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to update password.');
                }
            },
            complete: function() {
                pwSubmitting = false;
                btn.prop('disabled', false).text(origText);
            }
        });
    });
})(jQuery);
</script>
@endpush
