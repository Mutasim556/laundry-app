@php
    $biz = is_array($invoice->business_snapshot) ? $invoice->business_snapshot : [];
    $cust = is_array($invoice->customer_snapshot) ? $invoice->customer_snapshot : [];
    $groups = is_array($invoice->items_snapshot) ? $invoice->items_snapshot : [];
@endphp

<div class="a4-invoice">
    <!-- Header -->
    <div class="a4-header">
        <div class="a4-brand">
            @if(!empty($biz['logo_url']))
                <img src="{{ $biz['logo_url'] }}" alt="{{ $biz['name'] ?? 'Business Logo' }}" class="a4-logo">
            @endif
            <div class="a4-biz-name">{{ $biz['name'] ?? config('app.name', 'Laundry & Dry Cleaning') }}</div>
            @if(!empty($biz['address']))
                <div class="a4-biz-meta">{{ $biz['address'] }}</div>
            @endif
            <div class="a4-biz-meta">
                @if(!empty($biz['phone']))
                    <span>Phone: {{ $biz['phone'] }}</span>
                @endif
                @if(!empty($biz['phone']) && !empty($biz['email']))
                    <span> &middot; </span>
                @endif
                @if(!empty($biz['email']))
                    <span>Email: {{ $biz['email'] }}</span>
                @endif
            </div>
            @if(!empty($biz['vat_number']))
                <div class="a4-biz-meta">VAT / Tax Reg: {{ $biz['vat_number'] }}</div>
            @endif
        </div>

        <div class="a4-title-block">
            <div class="a4-title">INVOICE</div>
            <table class="a4-meta-table">
                <tr>
                    <th>Invoice No:</th>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                </tr>
                <tr>
                    <th>Issue Date:</th>
                    <td>{{ $invoice->issued_at?->format('d M Y, H:i') }}</td>
                </tr>
                <tr>
                    <th>Booking Ref:</th>
                    <td><strong>{{ $booking->reference }}</strong></td>
                </tr>
                <tr>
                    <th>Payment Status:</th>
                    <td><strong>{{ $invoice->payment_status }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Customer, Collection & Schedule -->
    <div class="a4-info-grid">
        <div class="a4-info-box">
            <div class="a4-box-title">Customer &amp; Collection</div>
            <div class="a4-info-strong">{{ $cust['name'] ?? $booking->customer_name }}</div>
            @if(!empty($cust['phone']))
                <div>Contact: {{ $cust['phone'] }}</div>
            @endif
            @if(!empty($cust['address']))
                <div>{{ $cust['address'] }}</div>
            @endif
            @if(!empty($cust['hotel_name']))
                <div>Hotel: {{ $cust['hotel_name'] }}@if(!empty($cust['room_number'])) (Room {{ $cust['room_number'] }})@endif</div>
            @endif
            @if(!empty($cust['postcode']))
                <div>Postcode: <strong>{{ $cust['postcode'] }}</strong></div>
            @endif
            @if(!empty($cust['assigned_store_name']))
                <div class="a4-mt-sm">Assigned Store: <strong>{{ $cust['assigned_store_name'] }}</strong></div>
            @endif
        </div>

        <div class="a4-info-box">
            <div class="a4-box-title">Booking Schedule</div>
            <div class="a4-sched-row">
                <span class="a4-sched-label">Collection:</span>
                <span>{{ $cust['collection_date'] ?? 'Not scheduled' }} @if(!empty($cust['collection_slot'])) ({{ $cust['collection_slot'] }}) @endif</span>
            </div>
            <div class="a4-sched-row">
                <span class="a4-sched-label">Delivery:</span>
                <span>{{ $cust['delivery_date'] ?? 'Not scheduled' }} @if(!empty($cust['delivery_slot'])) ({{ $cust['delivery_slot'] }}) @endif</span>
            </div>
            <div class="a4-sched-row a4-mt-sm">
                <span class="a4-sched-label">Payment Method:</span>
                <span>{{ $invoice->payment_gateway }}</span>
            </div>
        </div>
    </div>

    <!-- Itemized Service Table -->
    <table class="a4-items-table">
        <thead>
            <tr>
                <th>Service / Item</th>
                <th class="text-right">Qty / Weight</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $group)
                <tr class="a4-cat-row">
                    <td colspan="4">{{ $group['category_name'] ?? 'Services' }}</td>
                </tr>
                @foreach(($group['items'] ?? []) as $item)
                    <tr class="a4-item-row">
                        <td>
                            <div class="a4-item-name">{{ $item['service_name'] }}</div>
                            @if(!empty($item['item_name']))
                                <div class="a4-item-sub">Item: {{ $item['item_name'] }}</div>
                            @endif
                        </td>
                        <td class="text-right">{{ $item['qty_display'] }}</td>
                        <td class="text-right">{{ $item['unit_price_formatted'] }}</td>
                        <td class="text-right"><strong>{{ $item['line_total_formatted'] }}</strong></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <!-- Payment Summary -->
    <div class="a4-summary-wrap">
        <table class="a4-summary-table">
            <tr>
                <th>Items Subtotal</th>
                <td>{{ $currencyService->formatMinor((int) $invoice->items_subtotal_minor, $invoice->currency) }}</td>
            </tr>
            <tr>
                <th>Service Fee</th>
                <td>{{ $currencyService->formatMinor((int) $invoice->service_fee_minor, $invoice->currency) }}</td>
            </tr>
            <tr>
                <th>Collection &amp; Delivery Fee</th>
                <td>{{ $currencyService->formatMinor((int) $invoice->delivery_fee_minor, $invoice->currency) }}</td>
            </tr>
            <tr>
                <th>Discount</th>
                <td>-{{ $currencyService->formatMinor((int) $invoice->discount_minor, $invoice->currency) }}</td>
            </tr>
            <tr>
                <th>Tax</th>
                <td>{{ $currencyService->formatMinor((int) $invoice->tax_minor, $invoice->currency) }}</td>
            </tr>
            @if((int) $invoice->tip_minor > 0)
                <tr>
                    <th>Driver Tip</th>
                    <td>{{ $currencyService->formatMinor((int) $invoice->tip_minor, $invoice->currency) }}</td>
                </tr>
            @endif
            <tr class="a4-final-row">
                <th>Final Total</th>
                <td>{{ $currencyService->formatMinor((int) $invoice->final_total_minor, $invoice->currency) }}</td>
            </tr>
            <tr>
                <th>Payment Method</th>
                <td>{{ $invoice->payment_gateway }}</td>
            </tr>
            <tr>
                <th>Payment Status</th>
                <td><strong>{{ $invoice->payment_status }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="a4-footer">
        <div class="a4-footer-thanks">Thank you for choosing {{ $biz['name'] ?? config('app.name', 'Laundry & Dry Cleaning') }}.</div>
        <div class="a4-footer-note">
            @if(!empty($biz['phone']) || !empty($biz['email']))
                For billing or order inquiries, contact {{ $biz['phone'] ?: '' }}@if(!empty($biz['phone']) && !empty($biz['email'])) / @endif{{ $biz['email'] ?: '' }}.
            @endif
        </div>
    </div>
</div>
