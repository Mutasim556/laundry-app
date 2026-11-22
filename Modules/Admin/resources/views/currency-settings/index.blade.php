@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-4 rounded-4 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <div>
            <h2 class="mb-1 fw-bold text-white">Country &amp; Currency Settings</h2>
            <p class="mb-0 text-white-50 small">Configure the global business country and currency across the website, booking flow, and payment gateways.</p>
        </div>
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold">
            Active: {{ $current['business_country'] }} ({{ $current['default_currency'] }} — {{ $current['currency_symbol'] }})
        </span>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.currency-settings.update') }}" id="currencySettingsForm">
        @csrf
        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h5 class="mb-0 fw-bold">Global Country &amp; Currency</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Country</label>
                            <input type="hidden" name="business_country_code" id="businessCountryCodeInput" value="{{ old('business_country_code', $current['business_country_code']) }}">
                            <div class="position-relative" id="adminCountryDropdown">
                                <button type="button" class="form-select text-start d-flex align-items-center gap-2" id="adminCountryTrigger">
                                    <img src="{{ asset('admin/dist/css/icons/flag-icon-css/flags/' . strtolower(old('business_country_code', $current['business_country_code'])) . '.svg') }}"
                                         id="adminCountryFlag" width="22" height="16" class="rounded-1 border" style="object-fit: cover;">
                                    <span id="adminCountryLabel" class="fw-semibold">{{ $current['business_country'] }} ({{ $current['business_country_code'] }})</span>
                                </button>
                                <div class="card border shadow rounded-3 position-absolute start-0 w-100 mt-1 d-none" id="adminCountryMenu" style="z-index: 1050; max-height: 280px; overflow: hidden;">
                                    <div class="p-2 border-bottom bg-light">
                                        <input type="text" class="form-control form-control-sm" id="adminCountrySearch" placeholder="Search country or ISO code...">
                                    </div>
                                    <div class="list-group list-group-flush overflow-auto" style="max-height: 220px;">
                                        @foreach ($countries as $country)
                                            <button type="button"
                                                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3 small js-admin-country-opt"
                                                    data-iso="{{ $country['iso'] }}"
                                                    data-name="{{ $country['name'] }}"
                                                    data-default-currency="{{ $country['default_currency'] }}"
                                                    data-search="{{ strtolower($country['name'] . ' ' . $country['iso']) }}">
                                                <img src="{{ asset('admin/dist/css/icons/flag-icon-css/flags/' . strtolower($country['iso']) . '.svg') }}"
                                                     width="22" height="16" class="rounded-1 border flex-shrink-0" style="object-fit: cover;" loading="lazy">
                                                <span class="fw-semibold text-dark flex-grow-1">{{ $country['name'] }}</span>
                                                <span class="badge bg-light text-dark border">{{ $country['iso'] }} &rarr; {{ $country['default_currency'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="defaultCurrencySelect" class="form-label fw-semibold">Currency (Auto-selected, overrideable)</label>
                            <select name="default_currency" id="defaultCurrencySelect" class="form-select">
                                @foreach ($currencies as $code => $meta)
                                    <option value="{{ $code }}"
                                            data-symbol="{{ $meta['symbol'] }}"
                                            data-name="{{ $meta['name'] }}"
                                            data-decimals="{{ $meta['decimals'] }}"
                                            data-stripe="{{ $meta['stripe'] ? '1' : '0' }}"
                                            data-paypal="{{ $meta['paypal'] ? '1' : '0' }}"
                                            {{ old('default_currency', $current['default_currency']) === $code ? 'selected' : '' }}>
                                        {{ $code }} — {{ $meta['name'] }} ({{ $meta['symbol'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="currencyChangePolicyPanel" class="p-3 rounded-3 border border-warning bg-warning-subtle d-none">
                            <h6 class="fw-bold text-dark mb-1" id="currencyChangeHeadline"></h6>
                            <p class="small text-dark mb-2">
                                Confirmed historical bookings and past invoices will remain unchanged in <strong>{{ $current['default_currency'] }}</strong>. Choose how existing service prices ({{ $servicesCount }} services) and fee settings should be handled:
                            </p>

                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="repricing_policy" id="policyExchangeRate" value="exchange_rate"
                                        {{ old('repricing_policy', 'keep_manual') === 'exchange_rate' ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="policyExchangeRate">
                                        Convert existing prices using an approved exchange rate
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="repricing_policy" id="policyKeepManual" value="keep_manual"
                                        {{ old('repricing_policy', 'keep_manual') === 'keep_manual' ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="policyKeepManual">
                                        Switch currency label and reprice services manually afterward
                                    </label>
                                </div>
                            </div>

                            <div id="exchangeRateInputs" class="row g-2 mb-2 d-none">
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold mb-1">Exchange Rate (1 {{ $current['default_currency'] }} = ?)</label>
                                    <input type="number" step="0.000001" min="0.000001" name="exchange_rate" id="exchangeRateInput" class="form-control form-control-sm" value="{{ old('exchange_rate') }}" placeholder="e.g. 0.0065">
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label small fw-semibold mb-1">Rate Source (Audit)</label>
                                    <input type="text" name="rate_source" class="form-control form-control-sm" value="{{ old('rate_source', 'Manual Admin Rate') }}">
                                </div>
                                <div class="col-12 small text-dark" id="exchangeRatePreview"></div>
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="confirm_currency_change" value="1" id="confirmCurrencyChange">
                                <label class="form-check-label small fw-bold text-dark" for="confirmCurrencyChange">
                                    I confirm this global currency change and understand historical bookings remain in {{ $current['default_currency'] }}.
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top px-4 py-3 text-end">
                        <button type="submit" class="btn btn-primary px-4">Save Country &amp; Currency</button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h5 class="mb-0 fw-bold">Live Preview &amp; Gateway Compatibility</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="p-3 rounded-3 bg-light mb-3">
                            <div class="small text-muted">Selected Currency Display</div>
                            <div class="fs-4 fw-bold text-dark" id="previewCurrencyText">
                                {{ $current['default_currency'] }} — {{ $current['currency_name'] }} ({{ $current['currency_symbol'] }})
                            </div>
                            <div class="small text-secondary mt-1">
                                Sample Price Preview: <strong id="previewPriceSample">{{ formatMoney(600) }}</strong>
                            </div>
                        </div>

                        <h6 class="fw-bold small text-uppercase text-muted mb-2">Payment Gateway Support</h6>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Credit / Debit Card — Stripe</span>
                                <span id="badgeStripeCompat" class="badge {{ $current['stripe_supported'] ? 'bg-success' : 'bg-danger' }}">
                                    {{ $current['stripe_supported'] ? 'Supported' : 'Unsupported' }}
                                </span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>PayPal</span>
                                <span id="badgePaypalCompat" class="badge {{ $current['paypal_supported'] ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $current['paypal_supported'] ? 'Supported' : 'Unsupported (Auto-hidden at checkout)' }}
                                </span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>Cash on Delivery (COD)</span>
                                <span class="badge bg-success">Supported</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="mb-0 fw-bold">Currency Change Audit Log</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>Change</th>
                            <th>Policy</th>
                            <th>Admin</th>
                            <th class="pe-4">Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td class="ps-4 small text-muted">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="small fw-semibold">{{ $log->from_currency }} &rarr; {{ $log->to_currency }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $log->repricing_policy }}</span></td>
                                <td class="small">{{ $log->admin_name ?? 'Admin' }}</td>
                                <td class="pe-4 small">{{ $log->summary }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">No currency changes recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const currentCurrency = @json($current['default_currency']);
    const trigger = document.getElementById('adminCountryTrigger');
    const menu = document.getElementById('adminCountryMenu');
    const search = document.getElementById('adminCountrySearch');
    const countryInput = document.getElementById('businessCountryCodeInput');
    const countryLabel = document.getElementById('adminCountryLabel');
    const countryFlag = document.getElementById('adminCountryFlag');
    const currencySelect = document.getElementById('defaultCurrencySelect');
    const policyPanel = document.getElementById('currencyChangePolicyPanel');
    const headline = document.getElementById('currencyChangeHeadline');
    const rateWrap = document.getElementById('exchangeRateInputs');
    const rateInput = document.getElementById('exchangeRateInput');
    const ratePreview = document.getElementById('exchangeRatePreview');

    const updatePreview = () => {
        const opt = currencySelect.options[currencySelect.selectedIndex];
        const code = opt.value;
        const symbol = opt.dataset.symbol;
        const name = opt.dataset.name;
        const decimals = parseInt(opt.dataset.decimals || '2', 10);
        const stripeOk = opt.dataset.stripe === '1';
        const paypalOk = opt.dataset.paypal === '1';

        document.getElementById('previewCurrencyText').textContent = `${code} — ${name} (${symbol})`;
        document.getElementById('previewPriceSample').textContent = `${code} ${(600).toFixed(decimals)}`;

        const bStripe = document.getElementById('badgeStripeCompat');
        bStripe.className = `badge ${stripeOk ? 'bg-success' : 'bg-danger'}`;
        bStripe.textContent = stripeOk ? 'Supported' : 'Unsupported';

        const bPaypal = document.getElementById('badgePaypalCompat');
        bPaypal.className = `badge ${paypalOk ? 'bg-success' : 'bg-warning text-dark'}`;
        bPaypal.textContent = paypalOk ? 'Supported' : 'Unsupported (Auto-hidden at checkout)';

        const changed = code !== currentCurrency;
        policyPanel.classList.toggle('d-none', !changed);
        if (changed) {
            headline.textContent = `You are changing the website currency from ${currentCurrency} to ${code}.`;
        }
        const isRate = document.getElementById('policyExchangeRate').checked;
        rateWrap.classList.toggle('d-none', !isRate);
        if (isRate && rateInput.value > 0) {
            ratePreview.textContent = `Example: ${currentCurrency} 600.00 -> ${code} ${(600 * parseFloat(rateInput.value)).toFixed(decimals)}`;
        } else {
            ratePreview.textContent = '';
        }
    };

    trigger.addEventListener('click', () => {
        menu.classList.toggle('d-none');
        if (!menu.classList.contains('d-none')) search.focus();
    });

    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        document.querySelectorAll('.js-admin-country-opt').forEach(btn => {
            btn.classList.toggle('d-none', q !== '' && !btn.dataset.search.includes(q));
        });
    });

    document.querySelectorAll('.js-admin-country-opt').forEach(btn => {
        btn.addEventListener('click', () => {
            countryInput.value = btn.dataset.iso;
            countryLabel.textContent = `${btn.dataset.name} (${btn.dataset.iso})`;
            countryFlag.src = `{{ asset('admin/dist/css/icons/flag-icon-css/flags') }}/${btn.dataset.iso.toLowerCase()}.svg`;
            currencySelect.value = btn.dataset.defaultCurrency;
            menu.classList.add('d-none');
            updatePreview();
        });
    });

    document.addEventListener('click', (e) => {
        if (!document.getElementById('adminCountryDropdown').contains(e.target)) {
            menu.classList.add('d-none');
        }
    });

    currencySelect.addEventListener('change', updatePreview);
    document.getElementById('policyExchangeRate').addEventListener('change', updatePreview);
    document.getElementById('policyKeepManual').addEventListener('change', updatePreview);
    rateInput.addEventListener('input', updatePreview);
    updatePreview();
});
</script>
@endsection
