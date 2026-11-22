@php
    $selectedDial = old('country_code', $selectedCountryCode ?? '+44');
    $pickerId = $pickerId ?? 'customerCountryPicker';
@endphp
<div class="position-relative" id="{{ $pickerId }}">
    <input type="hidden" name="country_code" class="js-country-code-input" value="{{ $selectedDial }}">
    <button type="button"
            class="form-select booking-form-control text-start fw-semibold d-flex align-items-center justify-content-between js-country-picker-toggle"
            aria-expanded="false">
        <span class="js-country-picker-label">{{ $selectedDial }}</span>
    </button>
    <div class="card border shadow rounded-3 position-absolute start-0 mt-1 d-none js-country-picker-menu"
         style="z-index: 1050; width: 290px; max-width: calc(100vw - 32px); max-height: 260px; overflow: hidden;">
        <div class="p-2 border-bottom bg-light">
            <input type="text"
                   class="form-control form-control-sm js-country-picker-search"
                   placeholder="Search country or code...">
        </div>
        <div class="list-group list-group-flush overflow-auto js-country-picker-list" style="max-height: 205px;">
            @foreach ($countryList as $country)
                @php $isoLower = strtolower($country['iso']); @endphp
                <button type="button"
                        class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3 small js-country-picker-option"
                        data-dial="{{ $country['dial'] }}"
                        data-search="{{ strtolower($country['name'] . ' ' . $country['dial']) }}">
                    <span class="fw-bold text-dark flex-shrink-0" style="min-width: 48px;">{{ $country['dial'] }}</span>
                    <img src="{{ asset('admin/dist/css/icons/flag-icon-css/flags/' . $isoLower . '.svg') }}"
                         alt="{{ $country['name'] }} flag"
                         width="22"
                         height="16"
                         class="rounded-1 border flex-shrink-0"
                         style="width: 22px; height: 16px; object-fit: cover;"
                         loading="lazy">
                    <span class="text-dark text-truncate">{{ $country['name'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById(@json($pickerId));
    if (!root) return;
    const toggle = root.querySelector('.js-country-picker-toggle');
    const menu = root.querySelector('.js-country-picker-menu');
    const search = root.querySelector('.js-country-picker-search');
    const hidden = root.querySelector('.js-country-code-input');
    const label = root.querySelector('.js-country-picker-label');
    const options = Array.from(root.querySelectorAll('.js-country-picker-option'));

    toggle.addEventListener('click', () => {
        const isOpen = !menu.classList.contains('d-none');
        menu.classList.toggle('d-none', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        if (!isOpen) search.focus();
    });

    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        options.forEach(opt => {
            opt.classList.toggle('d-none', q !== '' && !opt.dataset.search.includes(q));
        });
    });

    options.forEach(opt => {
        opt.addEventListener('click', () => {
            hidden.value = opt.dataset.dial;
            label.textContent = opt.dataset.dial;
            menu.classList.add('d-none');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) {
            menu.classList.add('d-none');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
});
</script>
