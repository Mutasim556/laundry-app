@extends('store-portal.layouts.app')

@section('title', 'Store Profile & Bank Accounts')

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
    .sp-bank-item {
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 0.9rem 1rem;
        background: #FFFFFF;
    }
    .sp-bank-item.is-default {
        border-color: #FFC629;
        background: #FFFDF5;
    }
</style>
@endpush

@section('content')
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="sp-card p-3 p-md-4 h-100">
            <h5 class="fw-bold mb-3" style="color: #171717;">Store Information (Read-Only)</h5>
            <div class="row g-2 small">
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Store Name</div>
                    <div class="fw-bold">{{ $store->name }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Store Status</div>
                    <div class="fw-bold text-capitalize">{{ $store->status }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Public Phone</div>
                    <div class="fw-semibold">{{ trim(($store->phone_country_code ? $store->phone_country_code . ' ' : '') . $store->phone) ?: 'Not provided' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Store Email</div>
                    <div class="fw-semibold">{{ $store->email ?: 'Not provided' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted">Address</div>
                    <div class="fw-semibold">{{ $store->full_address ?: implode(', ', array_filter([$store->address_line_1, $store->city, $store->country_name])) }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Postcode</div>
                    <div class="fw-semibold">{{ $store->postcode ?: '-' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Platform Commission Rate</div>
                    <div class="fw-bold">{{ $store->commission_rate !== null ? number_format((float) $store->commission_rate, 2) . '%' : 'Not configured' }}</div>
                </div>
                @if($store->vendor)
                    <div class="col-12">
                        <div class="text-muted">Associated Vendor</div>
                        <div class="fw-semibold">{{ $store->vendor->name }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="sp-card p-3 p-md-4 h-100">
            <h5 class="fw-bold mb-3" style="color: #171717;">My Store User Account</h5>
            <form method="POST" action="{{ route('store.profile.update') }}" class="small">
                @csrf
                @method('PUT')
                <div class="mb-2">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $storeUser->name) }}" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Login Email (Managed by Admin)</label>
                    <input type="email" value="{{ $storeUser->email }}" class="form-control bg-light" readonly disabled>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label fw-semibold">Code</label>
                        <input type="text" name="phone_country_code" value="{{ old('phone_country_code', $storeUser->phone_country_code) }}" class="form-control" placeholder="+44">
                    </div>
                    <div class="col-8">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $storeUser->phone) }}" class="form-control" placeholder="7700900111">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('store.password.change') }}" class="small text-decoration-none fw-semibold">Change Password &rarr;</a>
                    <button type="submit" class="btn btn-sm sp-btn-yellow px-3">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Store Bank Accounts Section (AJAX) -->
<div class="sp-card p-3 p-md-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0"><i class="mdi mdi-bank me-1 text-primary"></i>Store Bank Accounts</h5>
            <div class="small text-muted">Save at least one eligible bank account before requesting a store withdrawal. Sensitive details are encrypted at rest.</div>
        </div>
        <button type="button" class="btn btn-sm sp-btn-yellow px-3" id="spOpenAddBankBtn">
            <i class="mdi mdi-plus-circle-outline me-1"></i>Add Bank Account
        </button>
    </div>

    <div id="spBankAlertBox" class="d-none alert py-2 small mb-3"></div>

    <div id="spBankAccountsList" class="d-grid gap-2">
        @forelse($bankAccounts as $acc)
            <div class="sp-bank-item {{ $acc['is_default'] ? 'is-default' : '' }}" data-account-id="{{ $acc['id'] }}">
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
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        @if(!$acc['is_default'])
                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 sp-bank-default-btn" data-id="{{ $acc['id'] }}">Set Default</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 sp-bank-edit-btn" data-account='@json($acc)'>Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 sp-bank-delete-btn" data-id="{{ $acc['id'] }}">Archive</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4 border rounded-3 bg-light small">
                <i class="mdi mdi-bank-off fs-3 d-block mb-1"></i>
                No store bank accounts saved yet. Click <strong>Add Bank Account</strong> to add one.
            </div>
        @endforelse
    </div>
</div>

<!-- Add / Edit Bank Account Modal -->
<div class="modal fade" id="spBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom" style="background: #FFF8E5;">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="spBankModalTitle">Add Store Bank Account</h6>
                    <div class="small text-muted">Account numbers are encrypted and masked in ordinary views.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="spBankForm" novalidate>
                <input type="hidden" id="spBankId" value="">
                <div class="modal-body p-3 p-md-4">
                    <div id="spBankModalAlert" class="alert alert-danger py-2 small d-none"></div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold mb-1">Bank Country <span class="text-danger">*</span></label>
                            <select name="country_code" id="spBankCountry" class="form-select form-select-sm" style="width:100%;">
                                @foreach($countriesMeta as $code => $meta)
                                    <option value="{{ $code }}" {{ ($store->country_code ?: 'GB') === $code ? 'selected' : '' }}>
                                        {{ $meta['name'] }} ({{ $code }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="country_code"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold mb-1">Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="spBankCurrency" class="form-select form-select-sm">
                                @foreach($currencies as $curCode => $curMeta)
                                    <option value="{{ $curCode }}" {{ $storeCurrency === $curCode ? 'selected' : '' }}>
                                        {{ $curCode }} — {{ $curMeta['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="currency"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Account Holder Name <span class="text-danger">*</span></label>
                            <input type="text" name="account_holder_name" id="spBankHolder" class="form-control form-control-sm" value="{{ $store->name }}" required>
                            <div class="invalid-feedback" data-field="account_holder_name"></div>
                        </div>
                        <div class="col-12 col-sm-7">
                            <label class="form-label small fw-semibold mb-1">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="spBankName" class="form-control form-control-sm" placeholder="e.g. Barclays, HSBC, Chase" required>
                            <div class="invalid-feedback" data-field="bank_name"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label class="form-label small fw-semibold mb-1">Account Type</label>
                            <select name="account_type" id="spBankType" class="form-select form-select-sm">
                                <option value="">Select (Optional)</option>
                                <option value="business">Business</option>
                                <option value="current">Current</option>
                                <option value="checking">Checking</option>
                                <option value="savings">Savings</option>
                            </select>
                            <div class="invalid-feedback" data-field="account_type"></div>
                        </div>
                        <div class="col-12 col-sm-7">
                            <label class="form-label small fw-semibold mb-1">Account Number <span class="text-danger" id="spAccNumReq">*</span></label>
                            <input type="text" name="account_number" id="spBankAccNum" class="form-control form-control-sm" autocomplete="off">
                            <div class="form-text small" id="spAccEditHint" style="display:none;">Leave blank when editing to keep existing encrypted account number.</div>
                            <div class="invalid-feedback" data-field="account_number"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label class="form-label small fw-semibold mb-1" id="spRoutingLabel">Sort Code</label>
                            <input type="text" name="routing_code" id="spBankRouting" class="form-control form-control-sm" autocomplete="off">
                            <div class="form-text small" id="spRoutingHint"></div>
                            <div class="invalid-feedback" data-field="routing_code"></div>
                        </div>
                        <div class="col-12 col-sm-7" id="spIbanGroup">
                            <label class="form-label small fw-semibold mb-1">IBAN (Optional)</label>
                            <input type="text" name="iban" id="spBankIban" class="form-control form-control-sm" autocomplete="off">
                            <div class="invalid-feedback" data-field="iban"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label class="form-label small fw-semibold mb-1">SWIFT / BIC (Optional)</label>
                            <input type="text" name="swift_bic" id="spBankSwift" class="form-control form-control-sm" autocomplete="off">
                            <div class="invalid-feedback" data-field="swift_bic"></div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="spBankDefault">
                                <label class="form-check-label small fw-semibold" for="spBankDefault">Set as default withdrawal bank account</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm sp-btn-yellow px-3" id="spBankSubmitBtn">Save Bank Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
jQuery(function($) {
    const countriesMeta = @json($countriesMeta);
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const storeUrl = @json(route('store.bank-accounts.store'));
    const baseUrl = @json(url('store/bank-accounts'));
    const bankModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('spBankModal'));

    if ($.fn.select2) {
        $('#spBankCountry').select2({ dropdownParent: $('#spBankModal'), width: '100%' });
    }

    function updateCountryFields(countryCode, autoSetCurrency = false) {
        const meta = countriesMeta[countryCode] || { routing_label: 'Routing / Branch Code', routing_required: false, routing_hint: '', iban_optional: true, currency: 'GBP' };
        $('#spRoutingLabel').html(meta.routing_label + (meta.routing_required ? ' <span class="text-danger">*</span>' : ' (Optional)'));
        $('#spRoutingHint').text(meta.routing_hint || '');
        $('#spIbanGroup').toggle(!!meta.iban_optional);
        if (autoSetCurrency && meta.currency) {
            $('#spBankCurrency').val(meta.currency);
        }
    }

    $('#spBankCountry').on('change', function() { updateCountryFields($(this).val(), true); });

    function clearFormErrors() {
        $('#spBankForm .is-invalid').removeClass('is-invalid');
        $('#spBankForm .invalid-feedback').text('');
        $('#spBankModalAlert').addClass('d-none').text('');
    }

    function showPageAlert(msg, type = 'success') {
        $('#spBankAlertBox').removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg);
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    function renderAccounts(accounts) {
        const list = $('#spBankAccountsList');
        if (!accounts || !accounts.length) {
            list.html('<div class="text-center text-muted py-4 border rounded-3 bg-light small"><i class="mdi mdi-bank-off fs-3 d-block mb-1"></i>No store bank accounts saved yet.</div>');
            return;
        }
        let html = '';
        accounts.forEach(acc => {
            html += `
                <div class="sp-bank-item ${acc.is_default ? 'is-default' : ''}" data-account-id="${acc.id}">
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
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            ${!acc.is_default ? `<button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 sp-bank-default-btn" data-id="${acc.id}">Set Default</button>` : ''}
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 sp-bank-edit-btn" data-account='${JSON.stringify(acc).replace(/'/g, "&#39;")}'>Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 sp-bank-delete-btn" data-id="${acc.id}">Archive</button>
                        </div>
                    </div>
                </div>
            `;
        });
        list.html(html);
    }

    $('#spOpenAddBankBtn').on('click', function() {
        clearFormErrors();
        $('#spBankId').val('');
        $('#spBankModalTitle').text('Add Store Bank Account');
        $('#spBankForm')[0].reset();
        $('#spBankCountry').val(@json($store->country_code ?: 'GB')).trigger('change');
        $('#spBankCurrency').val(@json($storeCurrency));
        $('#spBankHolder').val(@json($store->name));
        $('#spAccEditHint').hide();
        $('#spAccNumReq').show();
        bankModal.show();
    });

    $(document).on('click', '.sp-bank-edit-btn', function() {
        clearFormErrors();
        const acc = $(this).data('account');
        $('#spBankId').val(acc.id);
        $('#spBankModalTitle').text('Edit Store Bank Account (' + acc.account_number_masked + ')');
        $('#spBankCountry').val(acc.country_code).trigger('change');
        $('#spBankCurrency').val(acc.currency);
        $('#spBankHolder').val(acc.account_holder_name);
        $('#spBankName').val(acc.bank_name);
        $('#spBankType').val(acc.account_type || '');
        $('#spBankAccNum').val('');
        $('#spBankRouting').val('');
        $('#spBankIban').val('');
        $('#spBankSwift').val(acc.swift_bic || '');
        $('#spBankDefault').prop('checked', !!acc.is_default);
        $('#spAccEditHint').show();
        $('#spAccNumReq').hide();
        bankModal.show();
    });

    let submitting = false;
    $('#spBankForm').on('submit', function(e) {
        e.preventDefault();
        if (submitting) return;
        submitting = true;
        clearFormErrors();
        const btn = $('#spBankSubmitBtn').prop('disabled', true).text('Saving...');
        const id = $('#spBankId').val();
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
                showPageAlert(res.message || 'Store bank account saved.', 'success');
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(field => {
                        $(`#spBankForm [name="${field}"]`).addClass('is-invalid');
                        $(`#spBankForm .invalid-feedback[data-field="${field}"]`).text(errs[field][0]);
                    });
                } else {
                    $('#spBankModalAlert').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to save bank account.');
                }
            },
            complete: function() {
                submitting = false;
                btn.prop('disabled', false).text('Save Bank Account');
            }
        });
    });

    $(document).on('click', '.sp-bank-default-btn', function() {
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
                showPageAlert(xhr.responseJSON?.message || 'Failed to update default account.', 'danger');
            }
        });
    });

    $(document).on('click', '.sp-bank-delete-btn', function() {
        if (!confirm('Archive this bank account?')) return;
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
                const msg = xhr.responseJSON?.errors?.bank_account?.[0] || xhr.responseJSON?.message || 'Unable to archive bank account.';
                showPageAlert(msg, 'danger');
            }
        });
    });

    updateCountryFields($('#spBankCountry').val(), false);
});
</script>
@endpush
