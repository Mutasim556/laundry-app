@php
    $biz = is_array($invoice->business_snapshot) ? $invoice->business_snapshot : [];
    $cust = is_array($invoice->customer_snapshot) ? $invoice->customer_snapshot : [];
    $groups = is_array($invoice->items_snapshot) ? $invoice->items_snapshot : [];
    $widthClass = ($paperSize ?? '80mm') === '58mm' ? 'thermal-receipt--58mm' : 'thermal-receipt--80mm';
@endphp

<div class="thermal-receipt {{ $widthClass }}">
    <div class="tr-center tr-header">
        <div class="tr-biz-name">{{ $biz['name'] ?? config('app.name', 'Laundry & Dry Cleaning') }}</div>
        @if(!empty($biz['address']))
            <div class="tr-muted-line">{{ $biz['address'] }}</div>
        @endif
        @if(!empty($biz['phone']))
            <div class="tr-muted-line">Tel: {{ $biz['phone'] }}</div>
        @endif
        @if(!empty($biz['vat_number']))
            <div class="tr-muted-line">VAT: {{ $biz['vat_number'] }}</div>
        @endif
    </div>

    <div class="tr-divider"></div>

    <div class="tr-meta">
        <div class="tr-kv"><span>Invoice #:</span><strong>{{ $invoice->invoice_number }}</strong></div>
        <div class="tr-kv"><span>Booking Ref:</span><strong>{{ $booking->reference }}</strong></div>
        <div class="tr-kv"><span>Issued:</span><span>{{ $invoice->issued_at?->format('d M Y, H:i') }}</span></div>
    </div>

    <div class="tr-divider"></div>

    <div class="tr-section">
        <div class="tr-sec-title">CUSTOMER</div>
        <div><strong>{{ $cust['name'] ?? $booking->customer_name }}</strong></div>
        @if(!empty($cust['phone']))
            <div>Tel: {{ $cust['phone'] }}</div>
        @endif
        @if(!empty($cust['postcode']))
            <div>Postcode: {{ $cust['postcode'] }}</div>
        @endif
    </div>

    <div class="tr-section">
        <div class="tr-sec-title">BOOKING</div>
        @if(!empty($cust['collection_date']))
            <div class="tr-kv"><span>Collection:</span><span>{{ $cust['collection_date'] }} {{ $cust['collection_slot'] ? '(' . $cust['collection_slot'] . ')' : '' }}</span></div>
        @endif
        @if(!empty($cust['delivery_date']))
            <div class="tr-kv"><span>Delivery:</span><span>{{ $cust['delivery_date'] }} {{ $cust['delivery_slot'] ? '(' . $cust['delivery_slot'] . ')' : '' }}</span></div>
        @endif
        @if(!empty($cust['assigned_store_name']))
            <div class="tr-kv"><span>Store:</span><span>{{ $cust['assigned_store_name'] }}</span></div>
        @endif
    </div>

    <div class="tr-divider"></div>

    <div class="tr-section">
        <div class="tr-sec-title">SERVICES &amp; ITEMS</div>
        @foreach($groups as $group)
            <div class="tr-cat-title">{{ $group['category_name'] ?? 'Services' }}</div>
            @foreach(($group['items'] ?? []) as $item)
                <div class="tr-item">
                    <div class="tr-item-top">
                        <span class="tr-item-name">
                            {{ $item['service_name'] }}
                            @if(!empty($item['item_name']))
                                <span class="tr-item-sub">({{ $item['item_name'] }})</span>
                            @endif
                        </span>
                        <span class="tr-item-total">{{ $item['line_total_formatted'] }}</span>
                    </div>
                    <div class="tr-item-calc">
                        {{ $item['qty_display'] }} &times; {{ $item['unit_price_formatted'] }}
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    <div class="tr-divider"></div>

    <div class="tr-totals">
        <div class="tr-kv"><span>Items Subtotal</span><span>{{ $currencyService->formatMinor((int) $invoice->items_subtotal_minor, $invoice->currency) }}</span></div>
        <div class="tr-kv"><span>Service Fee</span><span>{{ $currencyService->formatMinor((int) $invoice->service_fee_minor, $invoice->currency) }}</span></div>
        <div class="tr-kv"><span>Collection &amp; Delivery</span><span>{{ $currencyService->formatMinor((int) $invoice->delivery_fee_minor, $invoice->currency) }}</span></div>
        @if((int) $invoice->discount_minor > 0)
            <div class="tr-kv"><span>Discount</span><span>-{{ $currencyService->formatMinor((int) $invoice->discount_minor, $invoice->currency) }}</span></div>
        @endif
        @if((int) $invoice->tax_minor > 0)
            <div class="tr-kv"><span>Tax</span><span>{{ $currencyService->formatMinor((int) $invoice->tax_minor, $invoice->currency) }}</span></div>
        @endif
        @if((int) $invoice->tip_minor > 0)
            <div class="tr-kv"><span>Driver Tip</span><span>{{ $currencyService->formatMinor((int) $invoice->tip_minor, $invoice->currency) }}</span></div>
        @endif

        <div class="tr-divider-solid"></div>

        <div class="tr-kv tr-grand-total">
            <span>FINAL TOTAL</span>
            <span>{{ $currencyService->formatMinor((int) $invoice->final_total_minor, $invoice->currency) }}</span>
        </div>

        <div class="tr-kv tr-pay-line"><span>Method</span><span>{{ $invoice->payment_gateway }}</span></div>
        <div class="tr-kv tr-pay-line"><span>Payment Status</span><strong>{{ $invoice->payment_status }}</strong></div>
    </div>

    <div class="tr-divider"></div>

    <div class="tr-center tr-footer">
        <div class="tr-thankyou">Thank you for your business!</div>
        <div>Ref: {{ $booking->reference }}</div>
        @if(!empty($biz['phone']) || !empty($biz['email']))
            <div class="tr-muted-line">Support: {{ $biz['phone'] ?: $biz['email'] }}</div>
        @endif
    </div>
</div>
