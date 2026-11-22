@php
    $accentPalette = ['#FFC629', '#0EA5E9', '#16A34A', '#8B5CF6', '#F97316'];
    $allAddedServiceIds = $booking->operationalItems->pluck('service_id')->filter()->map(fn ($id) => (int) $id);
@endphp

@forelse($categoryGroups as $group)
    @php
        $accentColor = $accentPalette[$loop->index % count($accentPalette)];
    @endphp
    <div class="card border shadow-none rounded-3 mb-3 js-category-group category-group-card"
         style="border-left: 4px solid {{ $accentColor }} !important;"
         data-category-id="{{ $group['category_id'] }}"
         data-category-name="{{ $group['category_name'] }}"
         data-is-wash-fold="{{ $group['is_wash_fold'] ? '1' : '0' }}">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center pb-2 mb-2 border-bottom">
                <div class="col-12 col-lg-5">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h6 class="fw-bold mb-0 text-dark">{{ $group['category_name'] }}</h6>
                            @if($group['source'] === 'customer')
                                <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.72rem;">Customer Selected</span>
                            @else
                                <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size: 0.72rem;">Admin Added</span>
                            @endif
                            @if(!empty($group['selected_option_name']))
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.72rem;">
                                    ✓ {{ $group['selected_option_name'] }}
                                </span>
                            @endif
                        </div>
                        @if(!empty($group['category_id']))
                            <button type="button"
                                class="btn btn-sm btn-outline-danger py-0 px-2 js-remove-category-btn"
                                data-category-id="{{ $group['category_id'] }}"
                                data-category-name="{{ $group['category_name'] }}">
                                Remove Category
                            </button>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    @if(!empty($group['services']))
                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <select class="form-select form-select-sm js-group-service-select js-select2-service"
                                        data-placeholder="Select {{ $group['category_name'] }} service...">
                                    <option value=""></option>
                                    @foreach($group['services'] as $svc)
                                        <option value="{{ $svc['id'] }}"
                                            data-added="{{ $allAddedServiceIds->contains((int) $svc['id']) ? '1' : '0' }}"
                                            data-service-name="{{ $svc['name'] }}"
                                            data-item-id="{{ $svc['item_id'] ?? '' }}"
                                            data-item-name="{{ $svc['item_name'] ?? '' }}"
                                            data-pricing-mode="{{ $svc['pricing_mode'] }}"
                                            data-unit-name="{{ $svc['unit_name'] }}"
                                            data-unit-short="{{ $svc['unit_short'] }}"
                                            data-price-major="{{ number_format((float) $svc['price'], $decimals, '.', '') }}">
                                            {{ $svc['name'] }}@if(!empty($svc['item_name'])) ({{ $svc['item_name'] }})@endif — {{ $svc['formatted_price'] }} / {{ $svc['unit_short'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if($group['is_wash_fold'])
                                <input type="text"
                                    class="form-control form-control-sm js-group-reason"
                                    style="max-width: 185px;"
                                    placeholder="Reason if switching">
                            @endif
                            <button type="button" class="btn btn-sm btn-yellow-add text-nowrap js-group-add-btn px-3">
                                + Add
                            </button>
                        </div>
                    @else
                        <div class="text-lg-end">
                            <span class="small text-muted fst-italic">No services configured for this category.</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="js-group-alert alert alert-danger py-2 px-3 small d-none mb-2"></div>

            <div class="js-group-empty text-muted small py-1 {{ $group['items']->isEmpty() ? '' : 'd-none' }}">
                No items added yet.
            </div>

            <div class="table-responsive js-group-table-wrap {{ $group['items']->isEmpty() ? 'd-none' : '' }}">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small">
                            <th>Actual Service / Item</th>
                            <th style="width: 125px;">Qty / Weight</th>
                            <th style="width: 95px;">Unit</th>
                            <th style="width: 135px;">Unit Price</th>
                            <th class="text-end" style="width: 130px;">Line Total</th>
                            <th style="width: 190px;">Override Reason</th>
                            <th class="text-end" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="js-group-tbody">
                        @foreach($group['items'] as $line)
                            @php
                                $unitMajor = number_format($currencyService->fromMinor((int) $line->actual_unit_price_minor, $booking->currency), $decimals, '.', '');
                            @endphp
                            <tr class="js-op-row" data-line-id="{{ $line->id }}" data-service-id="{{ $line->service_id }}" data-pricing-mode="{{ $line->pricing_mode }}">
                                <td>
                                    <div class="fw-semibold small">{{ $line->service_name }}</div>
                                    @if($line->item_name)
                                        <div class="text-muted" style="font-size: 0.75rem;">Item: {{ $line->item_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($line->pricing_mode === 'weight')
                                        <input type="number"
                                            step="0.01"
                                            min="0.01"
                                            name="items[{{ $line->id }}][weight_kg]"
                                            class="form-control form-control-sm js-line-qty"
                                            value="{{ old("items.{$line->id}.weight_kg", $line->weight_kg !== null ? number_format((float) $line->weight_kg, 2, '.', '') : '') }}"
                                            placeholder="kg">
                                    @else
                                        <input type="number"
                                            step="1"
                                            min="1"
                                            name="items[{{ $line->id }}][quantity]"
                                            class="form-control form-control-sm js-line-qty"
                                            value="{{ old("items.{$line->id}.quantity", $line->quantity) }}">
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $line->unit_name ?: ($line->pricing_mode === 'weight' ? 'kg' : 'Item') }}
                                </td>
                                <td>
                                    <input type="number"
                                        step="{{ $decimals === 0 ? '1' : '0.01' }}"
                                        min="0"
                                        name="items[{{ $line->id }}][actual_unit_price]"
                                        class="form-control form-control-sm js-line-price"
                                        value="{{ old("items.{$line->id}.actual_unit_price", $unitMajor) }}"
                                        @if(!$canOverridePrices) readonly @endif>
                                </td>
                                <td class="text-end fw-bold small js-line-total" data-minor="{{ (int) $line->line_total_minor }}">
                                    {{ $currencyService->formatMinor((int) $line->line_total_minor, $booking->currency) }}
                                </td>
                                <td>
                                    <input type="text"
                                        name="items[{{ $line->id }}][adjustment_reason]"
                                        class="form-control form-control-sm"
                                        value="{{ old("items.{$line->id}.adjustment_reason", $line->adjustment_reason) }}"
                                        placeholder="If price overridden">
                                </td>
                                <td class="text-end">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger py-0 px-2 js-remove-row-btn"
                                        data-line-id="{{ $line->id }}">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@empty
    <div class="text-center text-muted py-4">No service categories found. Click <strong>+ Add Service</strong> to add a service category.</div>
@endforelse
