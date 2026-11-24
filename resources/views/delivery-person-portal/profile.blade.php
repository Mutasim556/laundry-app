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
    .dp-bank-item {
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 0.9rem 1rem;
        background: #FFFFFF;
        transition: border-color 0.15s ease;
    }
    .dp-bank-item.is-default {
        border-color: #FFC629;
        background: #FFFDF5;
    }
</style>
@endpush

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="dp-card p-3 p-md-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="dp-brand-badge" style="width: 34px; height: 34px; font-size: 1rem;">
                    <i class="mdi mdi-account"></i>
                </span>
                <div>
                    <h6 class="fw-bold mb-0">{{ $person->full_name }}</h6>
                    <div class="small text-muted">{{ $person->code }}</div>
                </div>
            </div>
            <div class="small mb-3 d-grid gap-1">
                <div><strong>Email:</strong> {{ $person->email }}</div>
                <div><strong>Phone:</strong> {{ $person->phone_display }}</div>
                <div><strong>Assigned Store:</strong> {{ $person->store?->name ?? 'All Stores' }}</div>
                <div><strong>Earnings Currency:</strong> <span class="badge bg-light text-dark border">{{ strtoupper($person->currency ?: 'GBP') }}</span></div>
                <div><strong>Coverage Postcodes:</strong> {{ $person->postcodes->pluck('postcode')->implode(', ') ?: 'None' }}</div>
            </div>
            <form method="POST" action="{{ route('delivery-person.profile.update') }}" class="d-flex align-items-end gap-2 pt-2 border-top">
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

    <div class="col-12 col-lg-7">
        <div class="dp-card p-3 p-md-4 h-100">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0"><i class="mdi mdi-bank me-1 text-primary"></i>My Bank Accounts</h6>
                    <div class="small text-muted">Save at least one bank account to request earnings withdrawals. Sensitive details are encrypted at rest.</div>
                </div>
                <button type="button" class="btn btn-sm dp-btn-yellow px-3" id="dpOpenAddBankBtn">
                    <i class="mdi mdi-plus-circle-outline me-1"></i>Add Bank Account
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
                        <i class="mdi mdi-bank-off fs-3 d-block mb-1"></i>
                        No bank accounts saved yet. Click <strong>Add Bank Account</strong> to get started.
                    </div>
                @endforelse
            </div>
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
})(jQuery);
</script>
@endpush
