@forelse($unifiedTransactions as $row)
    <tr>
        <td class="ca-muted text-nowrap">{{ $row['occurred_at'] }}</td>
        <td class="fw-semibold">
            <span class="d-inline-block text-truncate align-middle" style="max-width: 150px;" title="{{ $row['reference'] }}">
                {{ $row['reference'] }}
            </span>
        </td>
        <td class="text-nowrap">
            @if($row['booking_id'] && $row['booking_reference'])
                <a href="{{ route('admin.bookings.show', $row['booking_id']) }}" class="text-decoration-none fw-semibold">{{ $row['booking_reference'] }}</a>
            @else
                <span class="ca-muted">-</span>
            @endif
        </td>
        <td class="text-nowrap">
            <div class="fw-semibold text-dark">{{ $row['transaction_type_label'] }}</div>
            <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">{{ $row['classification'] }}</span>
        </td>
        <td class="text-nowrap">{{ $row['counterparty'] }}</td>
        <td>
            <span class="d-inline-block text-truncate align-middle" style="max-width: 210px;" title="{{ $row['description'] }}">
                {{ $row['description'] }}
            </span>
        </td>
        <td class="text-end ca-money text-success text-nowrap">
            {{ $row['money_in_minor'] > 0 ? $currencyService->formatMinor($row['money_in_minor'], $row['currency']) : '-' }}
        </td>
        <td class="text-end ca-money text-danger text-nowrap">
            {{ $row['money_out_minor'] > 0 ? $currencyService->formatMinor($row['money_out_minor'], $row['currency']) : '-' }}
        </td>
        <td class="text-end ca-money ca-muted text-nowrap">
            {{ !$row['is_cash_movement'] ? $currencyService->formatMinor($row['accounting_amount_minor'], $row['currency']) : '-' }}
        </td>
        <td class="text-nowrap fw-semibold">{{ $row['currency'] }}</td>
        <td class="text-nowrap"><span class="badge {{ $row['status_badge'] }}">{{ $row['status'] }}</span></td>
        <td class="text-end text-nowrap">
            <button type="button"
                    class="btn btn-xs btn-outline-primary px-2 py-1 js-view-tx-detail"
                    data-url="{{ route('admin.current-account.detail', $row['uid']) }}">
                <i class="mdi mdi-eye me-1"></i>Details
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="12" class="text-center ca-muted py-4">
            <i class="mdi mdi-alert-circle me-1"></i>No transactions found for {{ $period['month_label'] }}.
        </td>
    </tr>
@endforelse
