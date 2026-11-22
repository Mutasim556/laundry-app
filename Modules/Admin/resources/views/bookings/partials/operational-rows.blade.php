@forelse($booking->operationalItems as $line)
    @php
        $unitMajor = number_format($currencyService->fromMinor((int) $line->actual_unit_price_minor, $booking->currency), $decimals, '.', '');
    @endphp
    <tr data-line-id="{{ $line->id }}" data-pricing-mode="{{ $line->pricing_mode }}">
        <td>
            <div class="fw-semibold">{{ $line->category_name ?: ($line->category?->name ?? 'Service') }}</div>
        </td>
        <td>
            <div class="fw-semibold">{{ $line->service_name }}</div>
            @if($line->item_name)
                <div class="small text-muted">Item: {{ $line->item_name }}</div>
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
        <td class="text-end fw-bold js-line-total" data-minor="{{ (int) $line->line_total_minor }}">
            {{ $currencyService->formatMinor((int) $line->line_total_minor, $booking->currency) }}
        </td>
        <td>
            <input type="text"
                name="items[{{ $line->id }}][adjustment_reason]"
                class="form-control form-control-sm"
                value="{{ old("items.{$line->id}.adjustment_reason", $line->adjustment_reason) }}"
                placeholder="Required if price overridden">
        </td>
        <td class="text-end">
            <button type="button"
                class="btn btn-sm btn-outline-danger"
                data-bs-toggle="modal"
                data-bs-target="#removeItemModal"
                data-remove-url="{{ route('admin.bookings.items.remove', [$booking, $line]) }}"
                data-item-label="{{ $line->service_name . ($line->item_name ? ' - ' . $line->item_name : '') }}">
                Remove
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center text-muted py-4">No operational items yet. Click <strong>+ Add Service</strong> to select a category and add services/items.</td>
    </tr>
@endforelse
