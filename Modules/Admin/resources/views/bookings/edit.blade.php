@extends('admin::layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
<style>
    .btn-yellow-add {
        background-color: #FFC629;
        border-color: #FFC629;
        color: #171717;
        font-weight: 600;
        border-radius: 8px;
    }
    .btn-yellow-add:hover, .btn-yellow-add:focus {
        background-color: #f2b718;
        border-color: #f2b718;
        color: #171717;
    }
    .category-group-card {
        background: #FFFFFF;
        transition: box-shadow 0.15s ease;
    }
    .category-group-card:hover {
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05) !important;
    }
    .select2-container {
        width: 100% !important;
    }
    .select2-container--default .select2-selection--single {
        min-height: 36px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        background-color: #FFFFFF;
    }
    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #FFC629;
        box-shadow: 0 0 0 0.2rem rgba(255, 198, 41, 0.25);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #171717;
        font-size: 0.875rem;
        line-height: 34px;
        padding-left: 10px;
        padding-right: 28px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 34px;
        right: 6px;
    }
    .select2-dropdown {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-results > .select2-results__options {
        max-height: 240px;
    }
    .select2-container--default .select2-results__option {
        font-size: 0.875rem;
        padding: 8px 12px;
        color: #171717;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #FFF8E5;
        color: #171717;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #FFF8E5;
        color: #171717;
        font-weight: 600;
    }
    .s2-option-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .s2-option-check {
        color: #16A34A;
        font-weight: 700;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
@php
    $decimals = $currencyService->decimals($booking->currency);
    $currencyCode = strtoupper($booking->currency ?: $currencyService->code());
    $canOverridePrices = auth('admin')->user()?->can('override-booking-prices', 'admin') ?? false;
    $origSnapshot = is_array($booking->services_snapshot) ? $booking->services_snapshot : [];
@endphp

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="text-decoration-none small">&larr; Back to Booking Details</a>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                <h3 class="fw-bold mb-0">Manage Booking {{ $booking->reference }}</h3>
                <span id="unsavedChangesBadge" class="badge bg-warning text-dark d-none">Unsaved Changes</span>
            </div>
            <div class="small text-muted">Customer: {{ $booking->customer_name }} ({{ $booking->customer_email }}) &middot; Version <span id="bookingVersionDisplay">{{ $booking->version }}</span></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-yellow-add px-3 py-2" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                + Add Service Category
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 py-2 px-3 small fw-semibold">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3 py-2 px-3 small">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($origSnapshot))
        <div class="card border-0 shadow-sm rounded-3 mb-3 bg-light">
            <div class="card-body py-2 px-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small fw-bold text-uppercase text-muted me-1">Confirmed Snapshot:</span>
                    @foreach($origSnapshot as $snap)
                        <span class="badge bg-white text-dark border px-2 py-1 rounded-pill" style="font-size: 0.78rem;">
                            {{ $snap['name'] ?? 'Category' }}
                            @if(!empty($snap['option_name']))
                                &mdash; <span class="text-primary">{{ $snap['option_name'] }}</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" id="manageBookingForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="version" id="bookingVersionInput" value="{{ $booking->version }}">
        <div id="deletedItemsContainer"></div>
        <div id="removedCategoriesInputs"></div>
        <div id="restoredCategoriesInputs"></div>

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0">Operational Services &amp; Items</h5>
                    <span class="small text-muted">Select one service per dropdown, click <strong>+ Add</strong>, then click <strong>Save Booking Changes</strong>.</span>
                </div>
                <div id="operationalCategoryGroups">
                    @include('admin::bookings.partials.category-groups', [
                        'booking' => $booking,
                        'categoryGroups' => $categoryGroups,
                        'currencyService' => $currencyService,
                        'decimals' => $decimals,
                        'currencyCode' => $currencyCode,
                        'canOverridePrices' => $canOverridePrices,
                    ])
                </div>

                <div id="removedCategoriesPanel" class="mt-3 pt-3 border-top {{ $removedCategories->isEmpty() ? 'd-none' : '' }}">
                    <div class="small fw-bold text-uppercase text-muted mb-2">Removed Operational Categories</div>
                    <div id="removedCategoriesList" class="d-flex flex-column gap-2">
                        @foreach($removedCategories as $rmCat)
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 bg-light border rounded-3 px-3 py-2 js-saved-removed-cat" data-category-id="{{ $rmCat['category_id'] }}">
                                <div class="small">
                                    <span class="fw-semibold text-dark">{{ $rmCat['category_name'] ?? 'Category' }}</span>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">Removed</span>
                                    @if(!empty($rmCat['reason']))
                                        <span class="text-muted ms-2">Reason: {{ $rmCat['reason'] }}</span>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 js-restore-saved-category-btn" data-category-id="{{ $rmCat['category_id'] }}">
                                    Restore Category
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-3">Fee Overrides &amp; Adjustments</h6>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Service Fee</label>
                            <select name="service_fee_override_mode" id="serviceFeeMode" class="form-select form-select-sm mb-2">
                                <option value="default" @selected(old('service_fee_override_mode', $booking->service_fee_override_mode) === 'default')>Default Global Fee</option>
                                <option value="custom" @selected(old('service_fee_override_mode', $booking->service_fee_override_mode) === 'custom')>Custom Amount</option>
                                <option value="waived" @selected(old('service_fee_override_mode', $booking->service_fee_override_mode) === 'waived')>Waived (0)</option>
                            </select>
                            <div class="row g-2">
                                <div class="col-sm-5">
                                    <input type="number"
                                        step="{{ $decimals === 0 ? '1' : '0.01' }}"
                                        min="0"
                                        name="service_fee_custom"
                                        id="serviceFeeCustom"
                                        class="form-control form-control-sm js-fee-input"
                                        value="{{ old('service_fee_custom', number_format($currencyService->fromMinor((int) $booking->service_fee_minor, $booking->currency), $decimals, '.', '')) }}"
                                        placeholder="Custom amount">
                                </div>
                                <div class="col-sm-7">
                                    <input type="text"
                                        name="service_fee_override_reason"
                                        class="form-control form-control-sm"
                                        value="{{ old('service_fee_override_reason') }}"
                                        placeholder="Reason (required if custom/waived)">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Collection &amp; Delivery Fee</label>
                            <select name="delivery_fee_override_mode" id="deliveryFeeMode" class="form-select form-select-sm mb-2">
                                <option value="default" @selected(old('delivery_fee_override_mode', $booking->delivery_fee_override_mode) === 'default')>Default Global Fee</option>
                                <option value="custom" @selected(old('delivery_fee_override_mode', $booking->delivery_fee_override_mode) === 'custom')>Custom Amount</option>
                                <option value="free" @selected(old('delivery_fee_override_mode', $booking->delivery_fee_override_mode) === 'free')>Free / Waived (0)</option>
                            </select>
                            <div class="row g-2">
                                <div class="col-sm-5">
                                    <input type="number"
                                        step="{{ $decimals === 0 ? '1' : '0.01' }}"
                                        min="0"
                                        name="delivery_fee_custom"
                                        id="deliveryFeeCustom"
                                        class="form-control form-control-sm js-fee-input"
                                        value="{{ old('delivery_fee_custom', number_format($currencyService->fromMinor((int) $booking->collection_delivery_fee_minor, $booking->currency), $decimals, '.', '')) }}"
                                        placeholder="Custom amount">
                                </div>
                                <div class="col-sm-7">
                                    <input type="text"
                                        name="delivery_fee_override_reason"
                                        class="form-control form-control-sm"
                                        value="{{ old('delivery_fee_override_reason') }}"
                                        placeholder="Reason (required if custom/free)">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Discount ({{ $currencyCode }})</label>
                                <input type="number"
                                    step="{{ $decimals === 0 ? '1' : '0.01' }}"
                                    min="0"
                                    name="discount"
                                    id="discountInput"
                                    class="form-control form-control-sm js-fee-input"
                                    value="{{ old('discount', number_format($currencyService->fromMinor((int) $booking->discount_minor, $booking->currency), $decimals, '.', '')) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Tax ({{ $currencyCode }})</label>
                                <input type="number"
                                    step="{{ $decimals === 0 ? '1' : '0.01' }}"
                                    min="0"
                                    name="tax"
                                    id="taxInput"
                                    class="form-control form-control-sm js-fee-input"
                                    value="{{ old('tax', number_format($currencyService->fromMinor((int) $booking->tax_minor, $booking->currency), $decimals, '.', '')) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-3">Status &amp; Operational Notes</h6>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold mb-1">Booking Status</label>
                                <select name="booking_status" class="form-select form-select-sm">
                                    <option value="{{ $booking->booking_status }}" selected>
                                        {{ $statusLabels[$booking->booking_status] ?? ucfirst($booking->booking_status) }} (Current)
                                    </option>
                                    @foreach($allowedNextStatuses as $nextStatus)
                                        <option value="{{ $nextStatus }}">{{ $statusLabels[$nextStatus] ?? ucfirst($nextStatus) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold mb-1">Operational Notes</label>
                                <textarea name="operational_notes" class="form-control form-control-sm" rows="3" placeholder="Internal weighing, stain, or collection notes...">{{ old('operational_notes', $booking->operational_notes) }}</textarea>
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                                <span>Items Subtotal Preview</span>
                                <span id="liveSubtotalPreview" class="fw-semibold text-dark">{{ $currencyService->formatMinor(0, $booking->currency) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted fw-semibold">Final Total</span>
                                <span class="fs-5 fw-bold text-dark" id="liveFinalTotal">
                                    @if($booking->final_amount_minor !== null)
                                        {{ $currencyService->formatMinor((int) $booking->final_amount_minor, $booking->currency) }}
                                    @else
                                        Pending Weight / Qty
                                    @endif
                                </span>
                            </div>
                            <div id="livePendingReason" class="small text-warning text-end mb-3 {{ empty($pendingReasons) ? 'd-none' : '' }}">
                                @if(!empty($pendingReasons))
                                    Pending: {{ $pendingReasons[0] }}
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Save Booking Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Add Service Category Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold">Add Service Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addServiceAlert" class="alert d-none rounded-3 small"></div>

                <div class="mb-3">
                    <label for="categorySelector" class="form-label fw-semibold small">Select Service Category</label>
                    <select id="categorySelector" class="form-select">
                        <option value="">-- Choose a Category --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                data-url="{{ route('admin.bookings.category-services', [$booking, $category]) }}">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 small text-uppercase text-muted">Available Services / Items</h6>
                        <span id="categoryServicesCount" class="small text-muted"></span>
                    </div>
                    <div id="categoryServicesContainer" class="border rounded-3 p-3 bg-light">
                        <div class="text-center text-muted small py-3">Select a service category above to view its available services and items.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Remove Entire Category Confirmation Modal -->
<div class="modal fade" id="removeCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold">Remove this entire category from the operational booking?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    All operational services and items under <strong id="removeCategoryNameLabel" class="text-dark"></strong> will be removed from the current booking. The original customer-confirmed booking record will remain unchanged.
                </p>
                <div id="removeCategoryModalError" class="alert alert-danger py-2 px-3 small d-none mb-2"></div>
                <div class="mb-0">
                    <label for="removeCategoryReasonInput" class="form-label small fw-semibold">Adjustment Reason <span class="text-danger">*</span></label>
                    <textarea id="removeCategoryReasonInput" class="form-control form-control-sm" rows="2" placeholder="Explain why this category is being removed..."></textarea>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmRemoveCategoryBtn">Remove Category</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = '{{ csrf_token() }}';
    const addServiceUrl = '{{ route('admin.bookings.services.add', $booking) }}';
    const currencyCode = @json($currencyCode);
    const decimals = {{ $decimals }};
    const defaultServiceFeeMajor = {{ (float) $defaultServiceFeeMajor }};
    const defaultDeliveryFeeMajor = {{ (float) $defaultDeliveryFeeMajor }};
    const tipMajor = {{ (float) $tipMajor }};
    const canOverridePrices = {{ $canOverridePrices ? 'true' : 'false' }};
    const preloadedCategoryServices = @json($categoriesServicesMap ?? []);

    const groupsContainer = document.getElementById('operationalCategoryGroups');
    const deletedContainer = document.getElementById('deletedItemsContainer');
    const removedCategoriesInputs = document.getElementById('removedCategoriesInputs');
    const restoredCategoriesInputs = document.getElementById('restoredCategoriesInputs');
    const removedCategoriesPanel = document.getElementById('removedCategoriesPanel');
    const removedCategoriesList = document.getElementById('removedCategoriesList');
    const unsavedBadge = document.getElementById('unsavedChangesBadge');
    let newRowCounter = 0;
    const stagedRemovedGroups = new Map();
    let pendingRemoveGroup = null;

    function markUnsaved() {
        if (unsavedBadge) unsavedBadge.classList.remove('d-none');
    }

    function formatMoney(val) {
        return currencyCode + ' ' + Number(val || 0).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function recalculatePreviews() {
        if (!groupsContainer) return;
        let itemsSubtotal = 0;
        const pendingReasons = [];

        const groups = groupsContainer.querySelectorAll('.js-category-group');
        groups.forEach(function (group) {
            const catName = group.getAttribute('data-category-name') || 'Category';
            const rows = group.querySelectorAll('tr.js-op-row');
            if (rows.length === 0) {
                pendingReasons.push(catName + ' has no selected items.');
            }
            rows.forEach(function (row) {
                const qtyInput = row.querySelector('.js-line-qty');
                const priceInput = row.querySelector('.js-line-price');
                const totalCell = row.querySelector('.js-line-total');
                const svcNameEl = row.querySelector('.fw-semibold');
                const svcName = svcNameEl ? svcNameEl.textContent.trim() : 'Service';
                const qtyVal = parseFloat(qtyInput ? qtyInput.value : '0');
                const priceVal = parseFloat(priceInput ? priceInput.value : '0');
                const safeQty = isNaN(qtyVal) ? 0 : Math.max(0, qtyVal);
                const safePrice = isNaN(priceVal) ? 0 : Math.max(0, priceVal);
                if (safeQty <= 0) {
                    pendingReasons.push(svcName + ' is missing quantity/weight.');
                }
                const lineTotal = safeQty * safePrice;
                itemsSubtotal += lineTotal;
                if (totalCell) {
                    totalCell.textContent = formatMoney(lineTotal);
                }
            });
        });

        const subtotalEl = document.getElementById('liveSubtotalPreview');
        if (subtotalEl) subtotalEl.textContent = formatMoney(itemsSubtotal);

        const svcFeeMode = document.getElementById('serviceFeeMode')?.value || 'default';
        const svcFeeVal = svcFeeMode === 'waived'
            ? 0
            : (svcFeeMode === 'custom'
                ? Math.max(0, parseFloat(document.getElementById('serviceFeeCustom')?.value || '0') || 0)
                : defaultServiceFeeMajor);

        const delFeeMode = document.getElementById('deliveryFeeMode')?.value || 'default';
        const delFeeVal = delFeeMode === 'free'
            ? 0
            : (delFeeMode === 'custom'
                ? Math.max(0, parseFloat(document.getElementById('deliveryFeeCustom')?.value || '0') || 0)
                : defaultDeliveryFeeMajor);

        const discountVal = Math.max(0, parseFloat(document.getElementById('discountInput')?.value || '0') || 0);
        const taxVal = Math.max(0, parseFloat(document.getElementById('taxInput')?.value || '0') || 0);

        const finalEl = document.getElementById('liveFinalTotal');
        const reasonEl = document.getElementById('livePendingReason');
        if (finalEl) {
            if (pendingReasons.length > 0) {
                finalEl.textContent = 'Pending Weight / Qty';
                if (reasonEl) {
                    reasonEl.textContent = 'Pending: ' + pendingReasons[0];
                    reasonEl.classList.remove('d-none');
                }
            } else {
                const finalTotal = Math.max(0, itemsSubtotal + svcFeeVal + delFeeVal + tipMajor + taxVal - discountVal);
                finalEl.textContent = formatMoney(finalTotal);
                if (reasonEl) {
                    reasonEl.textContent = '';
                    reasonEl.classList.add('d-none');
                }
            }
        }
    }

    function getActiveServiceIds() {
        const ids = new Set();
        if (!groupsContainer) return ids;
        groupsContainer.querySelectorAll('tr.js-op-row[data-service-id]').forEach(function (row) {
            const sid = parseInt(row.getAttribute('data-service-id') || '0', 10);
            if (sid > 0) ids.add(sid);
        });
        return ids;
    }

    function syncAddedServiceStates() {
        const activeIds = getActiveServiceIds();
        if (!groupsContainer) return;

        groupsContainer.querySelectorAll('.js-category-group').forEach(function (group) {
            const isWf = group.getAttribute('data-is-wash-fold') === '1';
            const select = group.querySelector('.js-group-service-select');
            const addBtn = group.querySelector('.js-group-add-btn');
            if (!select) return;

            Array.from(select.options).forEach(function (opt) {
                const sid = parseInt(opt.value || '0', 10);
                opt.setAttribute('data-added', sid > 0 && activeIds.has(sid) ? '1' : '0');
            });

            if (addBtn) {
                const selectedId = parseInt(select.value || '0', 10);
                const alreadyAdded = !isWf && selectedId > 0 && activeIds.has(selectedId);
                addBtn.disabled = alreadyAdded;
                addBtn.title = alreadyAdded ? 'This service has already been added.' : '';
            }
        });
    }

    function initSelect2Elements(scope, dropdownParent) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        const $ = jQuery;
        const $targets = scope ? $(scope).find('.js-select2-service') : $('.js-select2-service');

        $targets.each(function () {
            const $el = $(this);
            if ($el.data('select2')) return;

            $el.select2({
                width: '100%',
                placeholder: $el.attr('data-placeholder') || 'Select a service...',
                allowClear: false,
                dropdownParent: dropdownParent || undefined,
                templateResult: function (state) {
                    if (!state.id) return state.text;
                    const isAdded = state.element && state.element.getAttribute('data-added') === '1';
                    const isSelected = state.selected || (state.element && state.element.selected);

                    const $row = $('<span class="s2-option-row"></span>');
                    const $left = $('<span></span>').text(state.text);
                    if (isAdded) {
                        $left.append(' ').append($('<span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:0.7rem;">Added</span>'));
                    }
                    $row.append($left);
                    if (isAdded || isSelected) {
                        $row.append($('<span class="s2-option-check" aria-hidden="true">&#10003;</span>'));
                    }
                    return $row;
                },
                templateSelection: function (state) {
                    return state.text;
                }
            }).on('change', function () {
                syncAddedServiceStates();
            });
        });
    }

    initSelect2Elements(document);
    syncAddedServiceStates();
    recalculatePreviews();

    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        const $modal = jQuery('#addServiceModal');
        const $catSel = jQuery('#categorySelector');
        if ($catSel.length && !$catSel.data('select2')) {
            $catSel.select2({
                width: '100%',
                dropdownParent: $modal,
                placeholder: '-- Choose a Category --'
            });
        }
    }

    function appendNewRowToGroup(group, svc) {
        const tbody = group.querySelector('.js-group-tbody');
        const emptyBox = group.querySelector('.js-group-empty');
        const tableWrap = group.querySelector('.js-group-table-wrap');
        if (!tbody) return;

        newRowCounter += 1;
        const tmpKey = 'tmp_' + newRowCounter;
        const isWeight = svc.pricing_mode === 'weight';
        const priceStep = decimals === 0 ? '1' : '0.01';
        const catId = group.getAttribute('data-category-id') || '';

        const tr = document.createElement('tr');
        tr.className = 'js-op-row';
        tr.setAttribute('data-temp-key', tmpKey);
        tr.setAttribute('data-service-id', svc.id);
        tr.setAttribute('data-pricing-mode', svc.pricing_mode);

        const itemSub = svc.item_name
            ? '<div class="text-muted" style="font-size: 0.75rem;">Item: ' + escapeHtml(svc.item_name) + '</div>'
            : '';
        const qtyInputHtml = isWeight
            ? '<input type="number" step="0.01" min="0.01" name="new_items[' + tmpKey + '][weight_kg]" class="form-control form-control-sm js-line-qty" value="" placeholder="kg">'
            : '<input type="number" step="1" min="1" name="new_items[' + tmpKey + '][quantity]" class="form-control form-control-sm js-line-qty" value="1">';
        const readonlyAttr = canOverridePrices ? '' : ' readonly';
        const initialTotal = isWeight ? 0 : (parseFloat(svc.price_major || '0') || 0);

        tr.innerHTML =
            '<td>' +
                '<div class="fw-semibold small">' + escapeHtml(svc.name) + ' <span class="badge bg-warning-subtle text-dark border ms-1" style="font-size:0.68rem;">New</span></div>' +
                itemSub +
                '<input type="hidden" name="new_items[' + tmpKey + '][service_id]" value="' + svc.id + '">' +
                '<input type="hidden" name="new_items[' + tmpKey + '][category_id]" value="' + escapeHtml(catId) + '">' +
                '<input type="hidden" name="new_items[' + tmpKey + '][item_id]" value="' + escapeHtml(svc.item_id || '') + '">' +
            '</td>' +
            '<td>' + qtyInputHtml + '</td>' +
            '<td class="small text-muted">' + escapeHtml(svc.unit_name || (isWeight ? 'kg' : 'Item')) + '</td>' +
            '<td><input type="number" step="' + priceStep + '" min="0" name="new_items[' + tmpKey + '][actual_unit_price]" class="form-control form-control-sm js-line-price" value="' + escapeHtml(svc.price_major) + '"' + readonlyAttr + '></td>' +
            '<td class="text-end fw-bold small js-line-total">' + formatMoney(initialTotal) + '</td>' +
            '<td><input type="text" name="new_items[' + tmpKey + '][adjustment_reason]" class="form-control form-control-sm" value="' + escapeHtml(svc.reason || '') + '" placeholder="If price overridden"></td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 js-remove-row-btn" data-temp-key="' + tmpKey + '">Remove</button></td>';

        tbody.appendChild(tr);
        if (emptyBox) emptyBox.classList.add('d-none');
        if (tableWrap) tableWrap.classList.remove('d-none');

        markUnsaved();
        syncAddedServiceStates();
        recalculatePreviews();
    }

    if (groupsContainer) {
        groupsContainer.addEventListener('input', function (e) {
            if (!e.target.classList.contains('js-line-qty') && !e.target.classList.contains('js-line-price')) return;
            markUnsaved();
            recalculatePreviews();
        });

        groupsContainer.addEventListener('click', async function (e) {
            // Remove entire category -> open confirmation modal
            const removeCatBtn = e.target.closest('.js-remove-category-btn');
            if (removeCatBtn) {
                const group = removeCatBtn.closest('.js-category-group');
                if (!group) return;
                pendingRemoveGroup = group;
                const catName = removeCatBtn.getAttribute('data-category-name') || 'this category';
                document.getElementById('removeCategoryNameLabel').textContent = catName;
                document.getElementById('removeCategoryReasonInput').value = '';
                document.getElementById('removeCategoryModalError').classList.add('d-none');
                const modalEl = document.getElementById('removeCategoryModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
                return;
            }

            // Remove row (unsaved or existing)
            const removeBtn = e.target.closest('.js-remove-row-btn');
            if (removeBtn) {
                const row = removeBtn.closest('tr.js-op-row');
                const group = removeBtn.closest('.js-category-group');
                if (!row || !group) return;

                const lineId = removeBtn.getAttribute('data-line-id');
                if (lineId && deletedContainer) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'deleted_item_ids[]';
                    hidden.value = lineId;
                    deletedContainer.appendChild(hidden);
                }

                row.remove();
                const tbody = group.querySelector('.js-group-tbody');
                const emptyBox = group.querySelector('.js-group-empty');
                const tableWrap = group.querySelector('.js-group-table-wrap');
                if (tbody && tbody.querySelectorAll('tr.js-op-row').length === 0) {
                    if (emptyBox) emptyBox.classList.remove('d-none');
                    if (tableWrap) tableWrap.classList.add('d-none');
                }

                markUnsaved();
                syncAddedServiceStates();
                recalculatePreviews();
                return;
            }

            // Inline category "+ Add"
            const btn = e.target.closest('.js-group-add-btn');
            if (!btn || btn.disabled) return;

            const group = btn.closest('.js-category-group');
            const select = group ? group.querySelector('.js-group-service-select') : null;
            const reasonInput = group ? group.querySelector('.js-group-reason') : null;
            const groupAlert = group ? group.querySelector('.js-group-alert') : null;

            if (!select || !select.value) {
                if (groupAlert) {
                    groupAlert.textContent = 'Please select a service from the dropdown first.';
                    groupAlert.classList.remove('d-none');
                }
                return;
            }

            if (groupAlert) groupAlert.classList.add('d-none');

            const opt = select.options[select.selectedIndex];
            const serviceId = parseInt(select.value, 10);
            const isWashFold = group.getAttribute('data-is-wash-fold') === '1';

            if (!isWashFold && getActiveServiceIds().has(serviceId)) {
                if (groupAlert) {
                    groupAlert.textContent = 'This service has already been added.';
                    groupAlert.classList.remove('d-none');
                }
                syncAddedServiceStates();
                return;
            }

            // Wash & Fold single-option rule: prevent duplicate or unreasoned switch
            if (isWashFold) {
                const existingWfRow = group.querySelector('tr.js-op-row');
                if (existingWfRow) {
                    const existingSvcId = parseInt(existingWfRow.getAttribute('data-service-id') || '0', 10);
                    if (existingSvcId === serviceId) {
                        if (groupAlert) {
                            groupAlert.textContent = 'This Wash & Fold option is already active below. Enter its weight in kg.';
                            groupAlert.classList.remove('d-none');
                        }
                        return;
                    }
                    const reasonVal = reasonInput ? reasonInput.value.trim() : '';
                    if (!reasonVal) {
                        if (groupAlert) {
                            groupAlert.textContent = 'Changing the Wash & Fold option requires an adjustment reason.';
                            groupAlert.classList.remove('d-none');
                        }
                        return;
                    }
                    // Authorized switch via AJAX so Wash & Fold option updates cleanly
                    btn.disabled = true;
                    try {
                        const res = await fetch(addServiceUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                service_id: serviceId,
                                adjustment_reason: reasonVal,
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            if (groupAlert) {
                                groupAlert.textContent = data.message || 'Could not switch Wash & Fold option.';
                                groupAlert.classList.remove('d-none');
                            }
                            return;
                        }
                        if (data.groups_html) {
                            groupsContainer.innerHTML = data.groups_html;
                            initSelect2Elements(groupsContainer);
                            syncAddedServiceStates();
                        }
                        if (data.version) {
                            document.getElementById('bookingVersionInput').value = data.version;
                            document.getElementById('bookingVersionDisplay').textContent = data.version;
                        }
                        recalculatePreviews();
                    } finally {
                        btn.disabled = false;
                    }
                    return;
                }
            }

            appendNewRowToGroup(group, {
                id: serviceId,
                name: opt.getAttribute('data-service-name') || opt.textContent,
                item_id: opt.getAttribute('data-item-id') || '',
                item_name: opt.getAttribute('data-item-name') || '',
                pricing_mode: opt.getAttribute('data-pricing-mode') || 'item',
                unit_name: opt.getAttribute('data-unit-name') || 'Item',
                price_major: opt.getAttribute('data-price-major') || '0.00',
                reason: reasonInput ? reasonInput.value.trim() : '',
            });
        });
    }

    const confirmRemoveCatBtn = document.getElementById('confirmRemoveCategoryBtn');
    if (confirmRemoveCatBtn) {
        confirmRemoveCatBtn.addEventListener('click', function () {
            if (!pendingRemoveGroup) return;
            const reasonInput = document.getElementById('removeCategoryReasonInput');
            const errBox = document.getElementById('removeCategoryModalError');
            const reason = reasonInput ? reasonInput.value.trim() : '';
            if (reason.length < 3) {
                if (errBox) {
                    errBox.textContent = 'Please enter an adjustment reason (at least 3 characters).';
                    errBox.classList.remove('d-none');
                }
                return;
            }

            const catId = pendingRemoveGroup.getAttribute('data-category-id');
            const catName = pendingRemoveGroup.getAttribute('data-category-name') || 'Category';
            if (!catId) return;

            // Detach group from active list and preserve in stagedRemovedGroups for restore before save
            pendingRemoveGroup.remove();
            stagedRemovedGroups.set(catId, { element: pendingRemoveGroup, name: catName, reason: reason });

            if (removedCategoriesInputs) {
                const idInp = document.createElement('input');
                idInp.type = 'hidden';
                idInp.name = 'removed_categories[' + catId + '][category_id]';
                idInp.value = catId;
                idInp.setAttribute('data-rm-cat', catId);

                const reasonInp = document.createElement('input');
                reasonInp.type = 'hidden';
                reasonInp.name = 'removed_categories[' + catId + '][reason]';
                reasonInp.value = reason;
                reasonInp.setAttribute('data-rm-cat', catId);

                removedCategoriesInputs.appendChild(idInp);
                removedCategoriesInputs.appendChild(reasonInp);
            }

            if (removedCategoriesPanel && removedCategoriesList) {
                removedCategoriesPanel.classList.remove('d-none');
                const pill = document.createElement('div');
                pill.className = 'd-flex flex-wrap justify-content-between align-items-center gap-2 bg-light border rounded-3 px-3 py-2 js-staged-removed-cat';
                pill.setAttribute('data-category-id', catId);
                pill.innerHTML =
                    '<div class="small">' +
                        '<span class="fw-semibold text-dark">' + escapeHtml(catName) + '</span> ' +
                        '<span class="badge bg-warning-subtle text-dark border ms-1">Pending Removal</span> ' +
                        '<span class="text-muted ms-2">Reason: ' + escapeHtml(reason) + '</span>' +
                    '</div>' +
                    '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 js-restore-staged-category-btn" data-category-id="' + escapeHtml(catId) + '">Restore</button>';
                removedCategoriesList.appendChild(pill);
            }

            const modalEl = document.getElementById('removeCategoryModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

            pendingRemoveGroup = null;
            markUnsaved();
            syncAddedServiceStates();
            recalculatePreviews();
        });
    }

    if (removedCategoriesList) {
        removedCategoriesList.addEventListener('click', function (e) {
            const restoreStagedBtn = e.target.closest('.js-restore-staged-category-btn');
            if (restoreStagedBtn) {
                const catId = restoreStagedBtn.getAttribute('data-category-id');
                const entry = stagedRemovedGroups.get(catId);
                if (entry && groupsContainer) {
                    groupsContainer.appendChild(entry.element);
                    stagedRemovedGroups.delete(catId);
                }
                if (removedCategoriesInputs) {
                    removedCategoriesInputs.querySelectorAll('[data-rm-cat="' + catId + '"]').forEach(el => el.remove());
                }
                restoreStagedBtn.closest('.js-staged-removed-cat')?.remove();
                if (removedCategoriesList.children.length === 0 && removedCategoriesPanel) {
                    removedCategoriesPanel.classList.add('d-none');
                }
                initSelect2Elements(groupsContainer);
                syncAddedServiceStates();
                recalculatePreviews();
                return;
            }

            const restoreSavedBtn = e.target.closest('.js-restore-saved-category-btn');
            if (restoreSavedBtn) {
                const catId = restoreSavedBtn.getAttribute('data-category-id');
                if (catId && restoredCategoriesInputs) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'restored_category_ids[]';
                    inp.value = catId;
                    restoredCategoriesInputs.appendChild(inp);
                }
                restoreSavedBtn.closest('.js-saved-removed-cat')?.remove();
                if (removedCategoriesList.children.length === 0 && removedCategoriesPanel) {
                    removedCategoriesPanel.classList.add('d-none');
                }
                markUnsaved();
            }
        });
    }

    document.querySelectorAll('.js-fee-input, #serviceFeeMode, #deliveryFeeMode').forEach(function (el) {
        el.addEventListener('input', function () {
            markUnsaved();
            recalculatePreviews();
        });
        el.addEventListener('change', function () {
            markUnsaved();
            recalculatePreviews();
        });
    });

    // Global "+ Add Service Category" modal
    const categorySelector = document.getElementById('categorySelector');
    const servicesContainer = document.getElementById('categoryServicesContainer');
    const servicesCount = document.getElementById('categoryServicesCount');
    const alertBox = document.getElementById('addServiceAlert');

    function showModalAlert(type, message) {
        if (!alertBox) return;
        alertBox.className = 'alert alert-' + type + ' rounded-3 small';
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function renderCategoryServicesList(list) {
        servicesCount.textContent = list.length + ' service(s)';

        if (list.length === 0) {
            servicesContainer.innerHTML = '<div class="text-center text-muted small py-3">No configured services found under this category.</div>';
            return;
        }

        servicesContainer.innerHTML = list.map(function (svc) {
            const isWeight = svc.pricing_mode === 'weight';
            const inputHtml = isWeight
                ? '<input type="number" step="0.01" min="0.01" class="form-control form-control-sm js-modal-value" placeholder="Weight (kg)" value="">'
                : '<input type="number" step="1" min="1" class="form-control form-control-sm js-modal-value" placeholder="Qty" value="1">';
            const itemBadge = svc.item_name
                ? '<span class="badge bg-light text-dark border ms-1">' + escapeHtml(svc.item_name) + '</span>'
                : '';

            return (
                '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 bg-white border rounded-3 p-2 mb-2 js-service-option" data-service-id="' + svc.id + '" data-item-id="' + (svc.item_id || '') + '" data-pricing-mode="' + svc.pricing_mode + '">' +
                    '<div>' +
                        '<div class="fw-semibold small">' + escapeHtml(svc.name) + itemBadge + '</div>' +
                        '<div class="text-muted" style="font-size: 0.78rem;">' + escapeHtml(svc.formatted_price) + ' &middot; ' + escapeHtml(svc.unit_name) + '</div>' +
                    '</div>' +
                    '<div class="d-flex align-items-center gap-2">' +
                        '<div style="width: 120px;">' + inputHtml + '</div>' +
                        '<button type="button" class="btn btn-sm btn-yellow-add text-nowrap js-add-service-btn">+ Add</button>' +
                    '</div>' +
                '</div>'
            );
        }).join('');
    }

    if (categorySelector && servicesContainer) {
        const handleCategoryChange = async function () {
            if (alertBox) alertBox.classList.add('d-none');
            const selected = categorySelector.options[categorySelector.selectedIndex];
            const categoryId = selected ? selected.value : '';
            const url = selected ? selected.getAttribute('data-url') : null;

            if (!categoryId || !url) {
                servicesCount.textContent = '';
                servicesContainer.innerHTML = '<div class="text-center text-muted small py-3">Select a service category above to view its available services and items.</div>';
                return;
            }

            servicesContainer.innerHTML = '<div class="text-center text-muted small py-3">Loading services...</div>';

            try {
                const res = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                renderCategoryServicesList(Array.isArray(data.services) ? data.services : []);
            } catch (err) {
                if (Object.prototype.hasOwnProperty.call(preloadedCategoryServices, categoryId)) {
                    renderCategoryServicesList(preloadedCategoryServices[categoryId] || []);
                } else {
                    servicesContainer.innerHTML = '<div class="text-center text-danger small py-3">Failed to load services for this category.</div>';
                }
            }
        };

        if (typeof jQuery !== 'undefined') {
            jQuery(categorySelector).on('change', handleCategoryChange);
        } else {
            categorySelector.addEventListener('change', handleCategoryChange);
        }

        servicesContainer.addEventListener('click', async function (e) {
            const btn = e.target.closest('.js-add-service-btn');
            if (!btn || btn.disabled) return;

            const card = btn.closest('.js-service-option');
            const serviceId = card.getAttribute('data-service-id');
            const itemId = card.getAttribute('data-item-id') || null;
            const mode = card.getAttribute('data-pricing-mode');
            const valInput = card.querySelector('.js-modal-value');
            const rawVal = parseFloat(valInput ? valInput.value : '0');

            if (mode === 'weight' && (!(rawVal > 0))) {
                showModalAlert('danger', 'Please enter a positive weight in kg (e.g. 2.50).');
                return;
            }
            if (mode !== 'weight' && (!Number.isInteger(rawVal) || rawVal < 1)) {
                showModalAlert('danger', 'Please enter a positive integer quantity (1 or more).');
                return;
            }

            btn.disabled = true;
            try {
                const payload = {
                    service_id: parseInt(serviceId, 10),
                    item_id: itemId ? parseInt(itemId, 10) : null,
                };
                if (mode === 'weight') {
                    payload.weight_kg = rawVal;
                } else {
                    payload.quantity = rawVal;
                }

                const res = await fetch(addServiceUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();
                if (!res.ok) {
                    showModalAlert('danger', data.message || 'Could not add service.');
                    return;
                }

                if (groupsContainer && data.groups_html) {
                    groupsContainer.innerHTML = data.groups_html;
                    initSelect2Elements(groupsContainer);
                    syncAddedServiceStates();
                }
                if (data.version) {
                    document.getElementById('bookingVersionInput').value = data.version;
                    document.getElementById('bookingVersionDisplay').textContent = data.version;
                }
                recalculatePreviews();
                showModalAlert('success', data.message || 'Service added.');
            } catch (err) {
                showModalAlert('danger', 'Network error while adding service.');
            } finally {
                btn.disabled = false;
            }
        });
    }
});
</script>
@endpush
