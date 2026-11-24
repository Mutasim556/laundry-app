@extends('delivery-person-portal.layouts.app')
@section('title', 'Manage Booking ' . $booking->reference)
@section('page_title', 'Manage Booking ' . $booking->reference)

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
        border-radius: 12px;
        transition: box-shadow 0.15s ease;
    }
    .select2-container { width: 100% !important; }
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
</style>
@endpush

@section('content')
@php
    $decimals = $currencyService->decimals($booking->currency);
    $currencyCode = strtoupper($booking->currency ?: $currencyService->code());
    $existingCatIds = collect($categoryGroups)->pluck('category_id')->filter()->map(fn ($id) => (int) $id)->all();
@endphp

<!-- Header matching Admin Booking Edit -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <a href="{{ route('delivery-person.bookings.show', $booking) }}" class="small text-decoration-none fw-semibold text-primary d-inline-block mb-1">
            &larr; Back to Booking Details
        </a>
        <h4 class="fw-bold mb-0 text-dark">Manage Booking {{ $booking->reference }}</h4>
        <div class="small text-muted">
            Customer: {{ $booking->customer_name }} ({{ $booking->customer_phone }}) &bull; Version {{ (int) ($booking->version ?? 1) }}
        </div>
    </div>
    <button type="button" class="btn btn-yellow-add px-3 py-2" data-bs-toggle="modal" data-bs-target="#dpAddCategoryModal">
        + Add Service Category
    </button>
</div>

<!-- Confirmed Customer Snapshot (Immutable) -->
<div class="dp-card p-3 mb-3" style="background-color: #FFF8E5; border-color: #FDE68A;">
    <div class="d-flex flex-wrap align-items-center gap-2 small">
        <span class="fw-bold text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.04em;">CONFIRMED SNAPSHOT:</span>
        @forelse($booking->services_snapshot ?? [] as $snap)
            <span class="badge bg-white text-dark border px-2 py-1">
                {{ $snap['name'] ?? 'Service Category' }}
                @if(!empty($snap['option_name']))
                    &mdash; <span style="color: #6D28D9;">{{ $snap['option_name'] }}</span>
                @endif
            </span>
        @empty
            <span class="text-muted">No customer snapshot categories</span>
        @endforelse
    </div>
</div>

<!-- Operational Services & Items -->
<form method="POST" action="{{ route('delivery-person.bookings.update', $booking) }}" id="dpBookingEditForm">
    @csrf
    @method('PUT')

    <div id="dpHiddenInputs"></div>

    <div class="dp-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="fw-bold mb-0">Operational Services &amp; Items</h6>
            <div class="small text-muted">Select one service per dropdown, click <strong>+ Add</strong>, then click <strong>Save Booking Changes</strong>.</div>
        </div>

        <div id="dpCategoryCardsContainer">
            @foreach($categoryGroups as $group)
                @php
                    $catId = $group['category_id'];
                    $originType = $group['origin_type'] ?? 'unknown';
                    $borderColor = match ($originType) {
                        'customer' => '#FFC629',
                        'delivery_person' => '#16A34A',
                        'admin' => '#3B82F6',
                        default => '#64748B',
                    };
                    $badgeStyle = match ($originType) {
                        'customer' => 'background-color: #EDE9FE; color: #6D28D9;',
                        'delivery_person' => 'background-color: #DCFCE7; color: #15803D;',
                        'admin' => 'background-color: #DBEAFE; color: #1D4ED8;',
                        default => 'background-color: #F1F5F9; color: #475569;',
                    };
                @endphp
                <div class="category-group-card border p-3 mb-3 shadow-sm"
                     data-category-id="{{ $catId }}"
                     data-category-name="{{ $group['category_name'] }}"
                     style="border-left: 4px solid {{ $borderColor }} !important;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold text-dark">{{ $group['category_name'] }}</span>
                            <span class="badge rounded-pill px-2 py-1" style="font-size: 0.72rem; {{ $badgeStyle }}">
                                @if($originType !== 'delivery_person')
                                    <i class="mdi mdi-lock-outline me-1"></i>
                                @endif
                                {{ $group['origin_badge_label'] }}
                            </span>
                            @if(!empty($group['selected_option_name']))
                                <span class="badge rounded-pill px-2 py-1" style="background-color: #E0F2FE; color: #0369A1; font-size: 0.72rem;">
                                    &check; {{ $group['selected_option_name'] }}
                                </span>
                            @endif
                            @if($group['can_remove_category'] && $catId)
                                <button type="button"
                                        class="btn btn-xs btn-outline-danger js-dp-open-remove-cat"
                                        data-category-id="{{ $catId }}"
                                        data-category-name="{{ $group['category_name'] }}">
                                    Remove Category
                                </button>
                            @endif
                        </div>

                        @if($catId && !empty($group['services']))
                            <div class="d-flex align-items-center gap-2 flex-grow-1 flex-lg-grow-0" style="min-width: 260px;">
                                <div class="flex-grow-1">
                                    <select class="form-select form-select-sm js-dp-service-select" data-category-id="{{ $catId }}">
                                        <option value="">Select {{ $group['category_name'] }} service...</option>
                                        @foreach($group['services'] as $svc)
                                            <option value="{{ $svc['id'] }}"
                                                    data-name="{{ $svc['name'] }}"
                                                    data-mode="{{ $svc['pricing_mode'] }}"
                                                    data-unit="{{ $svc['unit_label'] }}"
                                                    data-price-minor="{{ $svc['price_minor'] }}"
                                                    data-price-dec="{{ $svc['price_decimal'] }}">
                                                {{ $svc['name'] }} ({{ $currencyCode }} {{ $svc['price_decimal'] }}/{{ $svc['unit_label'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-sm btn-yellow-add px-3 text-nowrap js-dp-add-service" data-category-id="{{ $catId }}">
                                    + Add
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Actual Service / Item</th>
                                    <th style="width: 110px;">Qty / Weight</th>
                                    <th>Unit</th>
                                    <th style="width: 110px;">Unit Price</th>
                                    <th class="text-end">Line Total</th>
                                    <th>Notes / Reason</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="js-dp-items-tbody" data-category-id="{{ $catId }}">
                                @forelse($group['items'] as $item)
                                    @php
                                        $unitMinor = (int) ($item->actual_unit_price_minor ?: $item->catalog_unit_price_minor);
                                        $unitDec = number_format($unitMinor / (10 ** $decimals), $decimals, '.', '');
                                    @endphp
                                    <tr data-existing-item-id="{{ $item->id }}">
                                        <td class="fw-semibold text-dark">
                                            {{ $item->item_name ?: $item->service_name }}
                                            <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                        </td>
                                        <td>
                                            @if($item->pricing_mode === 'weight')
                                                <input type="number" step="0.01" min="0" name="items[{{ $item->id }}][weight_kg]" value="{{ $item->weight_kg }}" class="form-control form-control-sm js-dp-qty-input" data-unit-minor="{{ $unitMinor }}">
                                            @else
                                                <input type="number" step="1" min="0" name="items[{{ $item->id }}][quantity]" value="{{ $item->quantity }}" class="form-control form-control-sm js-dp-qty-input" data-unit-minor="{{ $unitMinor }}">
                                            @endif
                                        </td>
                                        <td>{{ $item->pricing_mode === 'weight' ? 'kg' : ($item->unit_name ?: 'item') }}</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $unitDec }}" readonly tabindex="-1">
                                        </td>
                                        <td class="text-end fw-semibold js-dp-line-total">{{ $currencyService->formatMinor((int) $item->line_total_minor, $booking->currency) }}</td>
                                        <td>
                                            <input type="text" name="items[{{ $item->id }}][item_notes]" value="{{ $item->adjustment_reason }}" class="form-control form-control-sm" placeholder="Optional note">
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-xs btn-outline-danger js-dp-remove-existing-item" data-item-id="{{ $item->id }}">Remove</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="js-dp-empty-row"><td colspan="7" class="text-muted py-2">No items added yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="position-sticky bottom-0 bg-white border-top p-3 d-flex justify-content-end gap-2 rounded-3 shadow-sm">
        <a href="{{ route('delivery-person.bookings.show', $booking) }}" class="btn btn-sm btn-light px-3">Cancel</a>
        <button type="submit" class="btn btn-sm btn-yellow-add px-4">Save Booking Changes</button>
    </div>
</form>

<!-- Add Service Category Modal -->
<div class="modal fade" id="dpAddCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Add Service Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body small">
                <div id="dpAddCategoryError" class="alert alert-danger py-2 d-none"></div>
                <label class="form-label fw-semibold mb-1">Select Service Category</label>
                <select id="dpNewCategorySelect" class="form-select form-select-sm">
                    <option value="">Choose category...</option>
                    @foreach($categories as $cat)
                        @if(!in_array((int) $cat->id, $existingCatIds, true))
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-yellow-add px-3" id="dpConfirmAddCategoryBtn">Add Category</button>
            </div>
        </div>
    </div>
</div>

@if(collect($categoryGroups)->contains('can_remove_category', true))
{{-- Remove Category Confirmation Modal --}}
<div class="modal fade" id="dpRemoveCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Remove Category: <span id="dpRemoveCatName"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body small">
                <div id="dpRemoveCatError" class="alert alert-danger py-2 d-none"></div>
                <p class="mb-2">This will remove the category and its operational items from the current booking. The original customer snapshot remains unchanged.</p>
                <label class="form-label fw-semibold mb-1">Reason (Optional)</label>
                <input type="text" id="dpRemoveCatReason" class="form-control form-control-sm" placeholder="e.g. Added by mistake">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="dpConfirmRemoveCatBtn">Remove Category</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.min.js') }}"></script>
<script>
(function () {
    const currencyCode = @json($currencyCode);
    const decimals = @json($decimals);
    let newItemIndex = 0;
    let pendingRemoveCatId = null;

    function initSelect2($el) {
        if ($.fn.select2 && !$el.data('select2')) {
            $el.select2({ width: '100%' });
        }
    }
    $('.js-dp-service-select').each(function () { initSelect2($(this)); });

    $(document).on('input change', '.js-dp-qty-input', function () {
        const val = parseFloat($(this).val()) || 0;
        const unitMinor = parseInt($(this).data('unit-minor'), 10) || 0;
        const lineMinor = Math.round(val * unitMinor);
        $(this).closest('tr').find('.js-dp-line-total').text(currencyCode + ' ' + (lineMinor / Math.pow(10, decimals)).toFixed(decimals));
    });

    $(document).on('click', '.js-dp-remove-existing-item', function () {
        const itemId = $(this).data('item-id');
        $('#dpHiddenInputs').append('<input type="hidden" name="deleted_item_ids[]" value="' + itemId + '">');
        const $tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();
        if ($tbody.find('tr').length === 0) {
            $tbody.append('<tr class="js-dp-empty-row"><td colspan="7" class="text-muted py-2">No items added yet.</td></tr>');
        }
    });

    $(document).on('click', '.js-dp-remove-new-item', function () {
        const $tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();
        if ($tbody.find('tr').length === 0) {
            $tbody.append('<tr class="js-dp-empty-row"><td colspan="7" class="text-muted py-2">No items added yet.</td></tr>');
        }
    });

    $(document).on('click', '.js-dp-add-service', function () {
        const catId = $(this).data('category-id');
        const $card = $('.category-group-card[data-category-id="' + catId + '"]');
        const $sel = $card.find('.js-dp-service-select');
        const $opt = $sel.find('option:selected');
        const svcId = $sel.val();
        if (!svcId) return;

        const name = $opt.data('name');
        const mode = $opt.data('mode');
        const unit = $opt.data('unit');
        const unitMinor = parseInt($opt.data('price-minor'), 10) || 0;
        const unitDec = $opt.data('price-dec');
        const idx = newItemIndex++;

        const $tbody = $card.find('.js-dp-items-tbody');
        $tbody.find('.js-dp-empty-row').remove();

        const qtyInput = mode === 'weight'
            ? '<input type="number" step="0.01" min="0.01" name="new_items[' + idx + '][weight_kg]" value="1.00" class="form-control form-control-sm js-dp-qty-input" data-unit-minor="' + unitMinor + '">'
            : '<input type="number" step="1" min="1" name="new_items[' + idx + '][quantity]" value="1" class="form-control form-control-sm js-dp-qty-input" data-unit-minor="' + unitMinor + '">';

        const rowHtml = '<tr>'
            + '<td class="fw-semibold text-dark">' + $('<span>').text(name).html()
            + '<input type="hidden" name="new_items[' + idx + '][service_id]" value="' + svcId + '"></td>'
            + '<td>' + qtyInput + '</td>'
            + '<td>' + $('<span>').text(unit).html() + '</td>'
            + '<td><input type="text" class="form-control form-control-sm bg-light" value="' + unitDec + '" readonly tabindex="-1"></td>'
            + '<td class="text-end fw-semibold js-dp-line-total">' + currencyCode + ' ' + unitDec + '</td>'
            + '<td><input type="text" name="new_items[' + idx + '][item_notes]" class="form-control form-control-sm" placeholder="Optional note"></td>'
            + '<td class="text-end"><button type="button" class="btn btn-xs btn-outline-danger js-dp-remove-new-item">Remove</button></td>'
            + '</tr>';
        $tbody.append(rowHtml);
    });

    // Global "+ Add Service Category"
    $('#dpConfirmAddCategoryBtn').on('click', function () {
        const catId = $('#dpNewCategorySelect').val();
        if (!catId) return;
        const $btn = $(this).prop('disabled', true);
        $('#dpAddCategoryError').addClass('d-none');

        $.ajax({
            url: @json(route('delivery-person.bookings.categories.add', $booking)),
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { category_id: catId },
            success: function (res) {
                location.reload();
            },
            error: function (xhr) {
                $('#dpAddCategoryError').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to add category.');
                $btn.prop('disabled', false);
            }
        });
    });

    // Remove Delivery Person-added category
    $(document).on('click', '.js-dp-open-remove-cat', function () {
        pendingRemoveCatId = $(this).data('category-id');
        $('#dpRemoveCatName').text($(this).data('category-name'));
        $('#dpRemoveCatError').addClass('d-none');
        $('#dpRemoveCatReason').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dpRemoveCategoryModal')).show();
    });

    $('#dpConfirmRemoveCatBtn').on('click', function () {
        if (!pendingRemoveCatId) return;
        const $btn = $(this).prop('disabled', true);
        const url = @json(route('delivery-person.bookings.categories.remove', [$booking, '__CAT__'])).replace('__CAT__', pendingRemoveCatId);

        $.ajax({
            url: url,
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { reason: $('#dpRemoveCatReason').val() },
            success: function () {
                location.reload();
            },
            error: function (xhr) {
                $('#dpRemoveCatError').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to remove category.');
                $btn.prop('disabled', false);
            }
        });
    });
})();
</script>
@endpush
