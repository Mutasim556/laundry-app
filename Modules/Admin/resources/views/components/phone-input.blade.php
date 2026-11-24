@props([
    'idPrefix',
    'dialName' => 'phone_country_code',
    'phoneName' => 'phone',
    'defaultDial' => '+44',
    'defaultIso' => 'GB',
    'countries' => [],
    'required' => false,
    'placeholder' => 'Enter phone number',
])

@php
    $pickerId = $idPrefix . '_dial_picker';
    $phoneInputId = $idPrefix . '_phone_input';
    $dialInputId = $idPrefix . '_dial_input';
    $defaultIsoLower = strtolower($defaultIso ?: 'gb');
@endphp

<div class="intl-phone-group input-group">
    <div class="position-relative intl-dial-wrapper" id="{{ $pickerId }}" data-default-dial="{{ $defaultDial }}" data-default-iso="{{ $defaultIsoLower }}">
        <input type="hidden" name="{{ $dialName }}" id="{{ $dialInputId }}" class="js-intl-dial-value" value="{{ $defaultDial }}">
        <button type="button"
                class="btn btn-outline-secondary intl-dial-toggle d-flex align-items-center gap-2 js-intl-dial-toggle"
                aria-expanded="false"
                title="Select country dialing code">
            <img src="{{ asset('admin/dist/css/icons/flag-icon-css/flags/' . $defaultIsoLower . '.svg') }}"
                 alt=""
                 width="20"
                 height="14"
                 class="rounded-1 border flex-shrink-0 js-intl-dial-flag"
                 style="width: 20px; height: 14px; object-fit: cover;">
            <span class="fw-semibold js-intl-dial-code">{{ $defaultDial }}</span>
            <i class="mdi mdi-chevron-down font-14 text-muted"></i>
        </button>
        <div class="card border shadow-lg rounded-3 position-absolute start-0 mt-1 d-none js-intl-dial-menu intl-dial-menu">
            <div class="p-2 border-bottom bg-light">
                <input type="text"
                       class="form-control form-control-sm js-intl-dial-search"
                       placeholder="Search country or code...">
            </div>
            <div class="list-group list-group-flush overflow-auto js-intl-dial-list" style="max-height: 220px;">
                @foreach ($countries as $country)
                    @php $isoLower = strtolower($country['iso']); @endphp
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3 small js-intl-dial-option"
                            data-dial="{{ $country['dial'] }}"
                            data-iso="{{ $isoLower }}"
                            data-flag-url="{{ asset('admin/dist/css/icons/flag-icon-css/flags/' . $isoLower . '.svg') }}"
                            data-search="{{ strtolower($country['name'] . ' ' . $country['dial'] . ' ' . $country['iso']) }}">
                        <img src="{{ asset('admin/dist/css/icons/flag-icon-css/flags/' . $isoLower . '.svg') }}"
                             alt="{{ $country['name'] }} flag"
                             width="22"
                             height="16"
                             class="rounded-1 border flex-shrink-0"
                             style="width: 22px; height: 16px; object-fit: cover;"
                             loading="lazy">
                        <span class="text-dark text-truncate flex-grow-1 text-start">{{ $country['name'] }}</span>
                        <span class="fw-bold text-muted flex-shrink-0">{{ $country['dial'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    <input type="text"
           id="{{ $phoneInputId }}"
           name="{{ $phoneName }}"
           class="form-control intl-phone-input"
           placeholder="{{ $placeholder }}"
           @if($required) required @endif>
</div>
