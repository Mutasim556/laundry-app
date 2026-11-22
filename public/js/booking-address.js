(() => {
    'use strict';

    const app = document.querySelector('#bookingAddressApp');
    if (!app) return;

    const lookupUrl = app.dataset.lookupUrl || '/booking/postcode-lookup';
    const hotelSearchUrl = app.dataset.hotelUrl || '/booking/hotel-search';

    // Screens
    const screens = {
        choose: document.querySelector('#screenChooseType'),
        postcode: document.querySelector('#screenPostcodeAddress'),
        hotel: document.querySelector('#screenHotelSearch'),
        summary: document.querySelector('#screenAddressSummary'),
    };

    // Screen 1 elements
    const optionCards = document.querySelectorAll('[data-collection-option]');
    const btnChooseTypeNext = document.querySelector('#btnChooseTypeNext');
    let selectedCollectionMode = document.querySelector('.collection-option-card.is-selected')?.dataset.collectionOption || '';

    // Screen 2 (Postcode & Address) elements
    const formPostcode = document.querySelector('#formPostcodeAddress');
    const ukPostcodeInput = document.querySelector('#ukPostcodeInput');
    const btnFindPostcode = document.querySelector('#btnFindPostcode');
    const findBtnText = document.querySelector('#findBtnText');
    const findBtnSpinner = document.querySelector('#findBtnSpinner');
    const postcodeFeedback = document.querySelector('#postcodeFeedback');
    const postcodeSuccessHint = document.querySelector('#postcodeSuccessHint');
    const btnDontKnowPostcode = document.querySelector('#btnDontKnowPostcode');

    const addressDropdownSection = document.querySelector('#addressDropdownSection');
    const ukAddressIdInput = document.querySelector('#ukAddressIdInput');
    const selectedAddressInput = document.querySelector('#selectedAddressInput');
    const addressDropdownTrigger = document.querySelector('#addressDropdownTrigger');
    const addressTriggerLabel = document.querySelector('#addressTriggerLabel');
    const addressDropdownMenu = document.querySelector('#addressDropdownMenu');
    const addressListFilterInput = document.querySelector('#addressListFilterInput');
    const addressOptionsList = document.querySelector('#addressOptionsList');
    const btnAddressNotListed = document.querySelector('#btnAddressNotListed');
    const addressSelectError = document.querySelector('#addressSelectError');

    const manualAddressSection = document.querySelector('#manualAddressSection');
    const inputIsManual = document.querySelector('#inputIsManual');
    const manualAddressInput = document.querySelector('#manualAddressInput');
    const btnBackToDropdown = document.querySelector('#btnBackToDropdown');
    const manualAddressError = document.querySelector('#manualAddressError');

    const addressFollowupSection = document.querySelector('#addressFollowupSection');
    const addressTypeInput = document.querySelector('#addressTypeInput');
    const addressTypeBtns = document.querySelectorAll('[data-address-type]');
    const postcodeRoomNumberWrap = document.querySelector('#postcodeRoomNumberWrap');
    const postcodeRoomNumberInput = document.querySelector('#postcodeRoomNumberInput');
    const postcodeRoomNumberError = document.querySelector('#postcodeRoomNumberError');
    const addressDetailsInput = document.querySelector('#addressDetailsInput');
    const btnPostcodeNext = document.querySelector('#btnPostcodeNext');

    // Screen 3 (Hotel Search) elements
    const formHotel = document.querySelector('#formHotelAddress');
    const hotelIdInput = document.querySelector('#hotelIdInput');
    const hotelSearchInput = document.querySelector('#hotelSearchInput');
    const hotelSearchSpinner = document.querySelector('#hotelSearchSpinner');
    const btnHotelsNearMe = document.querySelector('#btnHotelsNearMe');
    const hotelGeoStatus = document.querySelector('#hotelGeoStatus');
    const hotelResultsContainer = document.querySelector('#hotelResultsContainer');
    const hotelResultsList = document.querySelector('#hotelResultsList');
    const hotelAddressInput = document.querySelector('#hotelAddressInput');
    const hotelRoomNumberInput = document.querySelector('#hotelRoomNumberInput');
    const hotelRoomNumberError = document.querySelector('#hotelRoomNumberError');
    const hotelCollectionTypeSection = document.querySelector('#hotelCollectionTypeSection');
    const hotelCollectionCards = document.querySelectorAll('[data-hotel-collection-card]');
    const hotelCollectionRadios = document.querySelectorAll('.js-hotel-collection-radio');
    const hotelCollectionTypeError = document.querySelector('#hotelCollectionTypeError');
    const hotelDetailsInput = document.querySelector('#hotelDetailsInput');
    const hotelNameError = document.querySelector('#hotelNameError');
    const hotelAddressError = document.querySelector('#hotelAddressError');
    const btnHotelNext = document.querySelector('#btnHotelNext');

    // Room number regex: letters, numbers, hyphens, spaces
    const ROOM_NUMBER_REGEX = /^[A-Za-z0-9\-\s]+$/;
    let cachedHotelRoomNumber = (postcodeRoomNumberInput?.value || hotelRoomNumberInput?.value || '').trim();

    // Standard UK Postcode regex
    const UK_POSTCODE_REGEX = /^([A-Z]{1,2}\d[A-Z\d]?|GIR)\s*(\d[A-Z]{2})$/i;

    let lastLookedUpPostcode = '';
    let cachedLookupAddresses = [];
    let lookupController = null;
    let hotelSearchController = null;
    let hotelDebounce = null;

    // =========================================================================
    // Helper: Uppercase Enforcement & UK Postcode Formatting
    // =========================================================================
    const forceUppercaseInput = (inputEl) => {
        if (!inputEl) return;
        const start = inputEl.selectionStart;
        const end = inputEl.selectionEnd;
        const upper = inputEl.value.toUpperCase();
        if (inputEl.value !== upper) {
            inputEl.value = upper;
            try {
                inputEl.setSelectionRange(start, end);
            } catch (_) {}
        }
    };

    const formatUkPostcode = (raw) => {
        const clean = String(raw || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (!clean) return '';
        // If clean length is between 5 and 7 and ends with digit + 2 letters, insert space before last 3 chars
        if (clean.length >= 5 && clean.length <= 7 && /\d[A-Z]{2}$/.test(clean)) {
            const outward = clean.slice(0, -3);
            const inward = clean.slice(-3);
            if (/^([A-Z]{1,2}\d[A-Z\d]?|GIR)$/.test(outward)) {
                return `${outward} ${inward}`;
            }
        }
        return String(raw || '').toUpperCase().replace(/\s+/g, ' ').trim();
    };

    const isValidUkPostcode = (postcode) => {
        const formatted = formatUkPostcode(postcode);
        return UK_POSTCODE_REGEX.test(formatted);
    };

    // Attach automatic UPPERCASE conversion to all address-related text fields
    [
        ukPostcodeInput,
        addressListFilterInput,
        manualAddressInput,
        postcodeRoomNumberInput,
        addressDetailsInput,
        hotelSearchInput,
        hotelAddressInput,
        hotelRoomNumberInput,
        hotelDetailsInput,
    ].forEach(el => {
        if (!el) return;
        el.addEventListener('input', () => forceUppercaseInput(el));
    });

    const isValidRoomNumber = (val) => {
        const trimmed = String(val || '').trim();
        return trimmed.length >= 1 && trimmed.length <= 50 && ROOM_NUMBER_REGEX.test(trimmed);
    };

    const syncRoomNumberInputs = (sourceInput) => {
        if (!sourceInput) return;
        const val = sourceInput.value;
        cachedHotelRoomNumber = val;
        if (sourceInput === postcodeRoomNumberInput && hotelRoomNumberInput) {
            hotelRoomNumberInput.value = val;
        } else if (sourceInput === hotelRoomNumberInput && postcodeRoomNumberInput) {
            postcodeRoomNumberInput.value = val;
        }
    };

    postcodeRoomNumberInput?.addEventListener('input', () => {
        syncRoomNumberInputs(postcodeRoomNumberInput);
        postcodeRoomNumberError?.classList.add('d-none');
        postcodeRoomNumberInput.classList.remove('is-invalid');
        validatePostcodeFormState();
    });

    hotelRoomNumberInput?.addEventListener('input', () => {
        syncRoomNumberInputs(hotelRoomNumberInput);
        hotelRoomNumberError?.classList.add('d-none');
        hotelRoomNumberInput.classList.remove('is-invalid');
        validateHotelFormState();
    });

    // =========================================================================
    // Screen Switching & History Navigation
    // =========================================================================
    const showScreen = (screenName, pushUrl = true) => {
        Object.entries(screens).forEach(([key, el]) => {
            if (!el) return;
            el.classList.toggle('d-none', key !== screenName);
        });

        app.dataset.activeScreen = screenName;

        if (pushUrl && window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('step', screenName);
            window.history.replaceState({ screen: screenName }, '', url);
        }

        validatePostcodeFormState();
        validateHotelFormState();
    };

    document.querySelectorAll('[data-go-screen]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.goScreen;
            if (target === 'hotel') {
                selectCollectionOption('hotel');
            } else if (target === 'postcode') {
                selectCollectionOption('home_office');
            }
            showScreen(target);
        });
    });

    // =========================================================================
    // SCREEN 1: Choose Home / Office vs Hotel
    // =========================================================================
    const selectCollectionOption = (mode) => {
        selectedCollectionMode = mode;
        optionCards.forEach(card => {
            const isMatch = card.dataset.collectionOption === mode;
            card.classList.toggle('is-selected', isMatch);
            card.setAttribute('aria-checked', isMatch ? 'true' : 'false');
        });
        if (btnChooseTypeNext) {
            btnChooseTypeNext.disabled = !selectedCollectionMode;
        }
    };

    optionCards.forEach(card => {
        card.addEventListener('click', () => {
            selectCollectionOption(card.dataset.collectionOption);
        });
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectCollectionOption(card.dataset.collectionOption);
            }
        });
    });

    btnChooseTypeNext?.addEventListener('click', () => {
        if (!selectedCollectionMode) return;
        if (selectedCollectionMode === 'hotel') {
            showScreen('hotel');
        } else {
            showScreen('postcode');
        }
    });

    // =========================================================================
    // SCREEN 2: UK Postcode Validation & Address Lookup
    // =========================================================================
    const showPostcodeError = (msg) => {
        if (!postcodeFeedback) return;
        postcodeFeedback.textContent = msg;
        postcodeFeedback.classList.remove('d-none');
        ukPostcodeInput?.classList.add('is-invalid');
        postcodeSuccessHint?.classList.add('d-none');
    };

    const clearPostcodeError = () => {
        postcodeFeedback?.classList.add('d-none');
        if (postcodeFeedback) postcodeFeedback.textContent = '';
        ukPostcodeInput?.classList.remove('is-invalid');
    };

    const showPostcodeHint = (msg) => {
        if (!postcodeSuccessHint) return;
        if (!msg) {
            postcodeSuccessHint.classList.add('d-none');
            return;
        }
        postcodeSuccessHint.textContent = msg;
        postcodeSuccessHint.classList.remove('d-none');
    };

    const renderAddressDropdownOptions = (addresses, filterQuery = '') => {
        if (!addressOptionsList) return;
        addressOptionsList.innerHTML = '';

        const normalizedFilter = filterQuery.trim().toUpperCase();
        const normalizedList = addresses.map(item => {
            if (item && typeof item === 'object') {
                return {
                    id: item.id ?? '',
                    full_address: String(item.full_address || '').toUpperCase().trim(),
                };
            }
            return {
                id: '',
                full_address: String(item || '').toUpperCase().trim(),
            };
        }).filter(item => item.full_address !== '');

        const filtered = normalizedList.filter(item => !normalizedFilter || item.full_address.includes(normalizedFilter));

        if (filtered.length === 0) {
            const emptyLi = document.createElement('li');
            emptyLi.className = 'address-option-empty';
            emptyLi.textContent = normalizedList.length === 0
                ? 'No matching addresses found in our directory for this postcode. Click "+ My address isn\'t listed" below to enter your address manually.'
                : 'No addresses match your filter. Click "+ My address isn\'t listed" below.';
            addressOptionsList.appendChild(emptyLi);
            return;
        }

        const currentVal = (selectedAddressInput?.value || '').toUpperCase();
        const currentId = String(ukAddressIdInput?.value || '');

        filtered.forEach(item => {
            const upperAddr = item.full_address;
            const itemId = item.id !== '' ? String(item.id) : '';
            const isSelected = (currentId && itemId && currentId === itemId) || upperAddr === currentVal;

            const li = document.createElement('li');
            li.className = 'address-option-item' + (isSelected ? ' is-selected' : '');
            li.setAttribute('role', 'option');
            li.setAttribute('tabindex', '0');
            li.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            li.dataset.addressValue = upperAddr;
            if (itemId) {
                li.dataset.addressId = itemId;
            }
            li.textContent = upperAddr;

            const chooseOption = () => {
                selectDropdownAddress(upperAddr, itemId);
                toggleAddressDropdown(false);
                addressDropdownTrigger?.focus();
            };

            li.addEventListener('click', chooseOption);
            li.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    chooseOption();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    li.nextElementSibling?.focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    (li.previousElementSibling || addressListFilterInput)?.focus();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    toggleAddressDropdown(false);
                    addressDropdownTrigger?.focus();
                }
            });

            addressOptionsList.appendChild(li);
        });
    };

    const toggleAddressDropdown = (open) => {
        if (!addressDropdownMenu || !addressDropdownTrigger) return;
        const isOpen = typeof open === 'boolean' ? open : !addressDropdownMenu.classList.contains('is-open');
        addressDropdownMenu.classList.toggle('is-open', isOpen);
        addressDropdownTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen && addressListFilterInput) {
            setTimeout(() => addressListFilterInput.focus(), 50);
        }
    };

    const selectDropdownAddress = (addressStr, addressId = '') => {
        const upper = String(addressStr || '').toUpperCase().trim();
        if (!upper) return;

        if (selectedAddressInput) selectedAddressInput.value = upper;
        if (ukAddressIdInput) ukAddressIdInput.value = addressId ? String(addressId) : '';
        if (addressTriggerLabel) addressTriggerLabel.textContent = upper;
        if (inputIsManual) inputIsManual.value = '0';

        // Highlight active option
        addressOptionsList?.querySelectorAll('.address-option-item').forEach(item => {
            const match = item.dataset.addressValue === upper;
            item.classList.toggle('is-selected', match);
            item.setAttribute('aria-selected', match ? 'true' : 'false');
        });

        addressSelectError?.classList.add('d-none');
        manualAddressSection?.classList.add('d-none');
        addressFollowupSection?.classList.remove('d-none');

        validatePostcodeFormState();
    };

    const enableManualAddressMode = (focusInput = true) => {
        if (inputIsManual) inputIsManual.value = '1';
        if (ukAddressIdInput) ukAddressIdInput.value = '';
        manualAddressSection?.classList.remove('d-none');
        addressFollowupSection?.classList.remove('d-none');
        toggleAddressDropdown(false);

        if (cachedLookupAddresses.length > 0) {
            btnBackToDropdown?.classList.remove('d-none');
            addressDropdownSection?.classList.add('d-none');
        }

        if (focusInput && manualAddressInput) {
            manualAddressInput.focus();
        }

        validatePostcodeFormState();
    };

    const performPostcodeLookup = async (force = false) => {
        if (!ukPostcodeInput) return;
        forceUppercaseInput(ukPostcodeInput);

        const raw = ukPostcodeInput.value.trim();
        if (!raw) {
            showPostcodeError('Please enter a UK postcode, such as E1 0BJ.');
            addressDropdownSection?.classList.add('d-none');
            validatePostcodeFormState();
            return;
        }

        const formatted = formatUkPostcode(raw);
        ukPostcodeInput.value = formatted;

        if (!isValidUkPostcode(formatted)) {
            showPostcodeError('Please enter a complete, valid UK postcode (e.g. E1 0BJ or SW1A 1AA).');
            addressDropdownSection?.classList.add('d-none');
            validatePostcodeFormState();
            return;
        }

        clearPostcodeError();

        // Avoid duplicate lookup requests for the same formatted postcode unless forced
        if (!force && formatted === lastLookedUpPostcode && cachedLookupAddresses.length > 0) {
            if (inputIsManual?.value !== '1') {
                addressDropdownSection?.classList.remove('d-none');
            }
            validatePostcodeFormState();
            return;
        }

        lookupController?.abort();
        lookupController = new AbortController();

        if (findBtnText) findBtnText.textContent = 'Looking up...';
        findBtnSpinner?.classList.remove('d-none');
        if (btnFindPostcode) btnFindPostcode.disabled = true;

        try {
            const url = new URL(lookupUrl, window.location.origin);
            url.searchParams.set('postcode', formatted);

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: lookupController.signal,
            });

            const data = await response.json();

            if (!response.ok || data.valid === false) {
                lastLookedUpPostcode = '';
                cachedLookupAddresses = [];
                addressDropdownSection?.classList.add('d-none');
                showPostcodeError(data.message || 'We could not verify this UK postcode. Please check and try again.');
                validatePostcodeFormState();
                return;
            }

            lastLookedUpPostcode = data.postcode || formatted;
            ukPostcodeInput.value = lastLookedUpPostcode;
            cachedLookupAddresses = (Array.isArray(data.address_items) && data.address_items.length > 0)
                ? data.address_items
                : (Array.isArray(data.addresses) ? data.addresses : []);

            showPostcodeHint(data.notice || `Verified UK postcode ${lastLookedUpPostcode}.`);

            renderAddressDropdownOptions(cachedLookupAddresses);

            if (data.lookup_unavailable) {
                // Provider unreachable: automatically open manual address entry fallback
                enableManualAddressMode(true);
            } else {
                if (inputIsManual?.value !== '1') {
                    addressDropdownSection?.classList.remove('d-none');
                    toggleAddressDropdown(true);
                }
            }
        } catch (err) {
            if (err.name === 'AbortError') return;
            showPostcodeHint('Live lookup could not be reached. Please enter your address manually below.');
            enableManualAddressMode(true);
        } finally {
            if (findBtnText) findBtnText.textContent = 'Find Address';
            findBtnSpinner?.classList.add('d-none');
            if (btnFindPostcode) btnFindPostcode.disabled = false;
            validatePostcodeFormState();
        }
    };

    // Postcode input events
    ukPostcodeInput?.addEventListener('blur', () => {
        const val = ukPostcodeInput.value.trim();
        if (!val) return;
        const formatted = formatUkPostcode(val);
        ukPostcodeInput.value = formatted;
        if (!isValidUkPostcode(formatted)) {
            showPostcodeError('Please enter a valid UK postcode (for example, E1 0BJ).');
            addressDropdownSection?.classList.add('d-none');
        } else {
            clearPostcodeError();
            if (formatted !== lastLookedUpPostcode) {
                performPostcodeLookup();
            }
        }
        validatePostcodeFormState();
    });

    ukPostcodeInput?.addEventListener('input', () => {
        clearPostcodeError();
        const raw = ukPostcodeInput.value.trim();
        const clean = raw.replace(/\s+/g, '');
        // Auto-format once a complete valid UK postcode length is typed
        if (clean.length >= 5 && clean.length <= 7) {
            const formatted = formatUkPostcode(raw);
            if (isValidUkPostcode(formatted)) {
                ukPostcodeInput.value = formatted;
            }
        }
        validatePostcodeFormState();
    });

    ukPostcodeInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            performPostcodeLookup(true);
        }
    });

    btnFindPostcode?.addEventListener('click', () => {
        performPostcodeLookup(true);
    });

    // Dropdown trigger & filter events
    addressDropdownTrigger?.addEventListener('click', () => {
        toggleAddressDropdown();
    });

    addressListFilterInput?.addEventListener('input', () => {
        renderAddressDropdownOptions(cachedLookupAddresses, addressListFilterInput.value);
    });

    // "My address isn't listed" & "Don't know your postcode?"
    btnAddressNotListed?.addEventListener('click', () => {
        enableManualAddressMode(true);
    });

    btnDontKnowPostcode?.addEventListener('click', () => {
        enableManualAddressMode(true);
        if (ukPostcodeInput && !ukPostcodeInput.value.trim()) {
            ukPostcodeInput.focus();
        }
    });

    btnBackToDropdown?.addEventListener('click', () => {
        if (inputIsManual) inputIsManual.value = '0';
        manualAddressSection?.classList.add('d-none');
        addressDropdownSection?.classList.remove('d-none');
        toggleAddressDropdown(true);
        validatePostcodeFormState();
    });

    manualAddressInput?.addEventListener('input', () => {
        manualAddressError?.classList.add('d-none');
        if (manualAddressInput.value.trim().length >= 3) {
            addressFollowupSection?.classList.remove('d-none');
        }
        validatePostcodeFormState();
    });

    // Saved address quick-select buttons
    document.querySelectorAll('[data-saved-address]').forEach(btn => {
        btn.addEventListener('click', () => {
            const pc = btn.dataset.savedPostcode || '';
            const addr = btn.dataset.savedAddress || '';
            const type = btn.dataset.savedType || 'HOME';
            const details = btn.dataset.savedDetails || '';

            if (ukPostcodeInput) ukPostcodeInput.value = pc;
            lastLookedUpPostcode = pc;
            clearPostcodeError();

            if (selectedAddressInput) selectedAddressInput.value = addr;
            if (addressTriggerLabel) addressTriggerLabel.textContent = addr;
            if (inputIsManual) inputIsManual.value = '0';

            addressDropdownSection?.classList.remove('d-none');
            manualAddressSection?.classList.add('d-none');
            addressFollowupSection?.classList.remove('d-none');

            setAddressType(type);
            if (addressDetailsInput) addressDetailsInput.value = details;
            validatePostcodeFormState();
        });
    });

    // =========================================================================
    // Address Type Selection (HOME | OFFICE | HOTEL)
    // =========================================================================
    const setAddressType = (type) => {
        const normalized = String(type || 'HOME').toUpperCase();
        if (addressTypeInput) addressTypeInput.value = normalized;

        addressTypeBtns.forEach(btn => {
            const active = btn.dataset.addressType === normalized;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-checked', active ? 'true' : 'false');
        });

        const isHotel = normalized === 'HOTEL';
        if (postcodeRoomNumberWrap) {
            postcodeRoomNumberWrap.classList.toggle('d-none', !isHotel);
        }
        if (postcodeRoomNumberInput) {
            if (isHotel) {
                postcodeRoomNumberInput.disabled = false;
                if (!postcodeRoomNumberInput.value.trim() && cachedHotelRoomNumber) {
                    postcodeRoomNumberInput.value = cachedHotelRoomNumber;
                }
            } else {
                if (postcodeRoomNumberInput.value.trim()) {
                    cachedHotelRoomNumber = postcodeRoomNumberInput.value.trim();
                }
                postcodeRoomNumberInput.disabled = true;
                postcodeRoomNumberError?.classList.add('d-none');
                postcodeRoomNumberInput.classList.remove('is-invalid');
            }
        }

        validatePostcodeFormState();
    };

    addressTypeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            setAddressType(btn.dataset.addressType);
        });
    });

    // =========================================================================
    // Validate Postcode Form State & Enable/Disable Next Button
    // =========================================================================
    function validatePostcodeFormState() {
        if (!btnPostcodeNext || !ukPostcodeInput) return;

        const formattedPc = formatUkPostcode(ukPostcodeInput.value);
        const pcValid = isValidUkPostcode(formattedPc);
        const isManual = inputIsManual?.value === '1';

        const chosenAddress = isManual
            ? (manualAddressInput?.value || '').trim()
            : (selectedAddressInput?.value || '').trim();

        const hasValidAddress = chosenAddress.length >= 5;
        const currentAddrType = (addressTypeInput?.value || '').toUpperCase();
        const hasAddressType = ['HOME', 'OFFICE', 'HOTEL'].includes(currentAddrType);

        const roomValid = currentAddrType !== 'HOTEL'
            || isValidRoomNumber(postcodeRoomNumberInput?.value || '');

        btnPostcodeNext.disabled = !(pcValid && hasValidAddress && hasAddressType && roomValid);
    }

    formPostcode?.addEventListener('submit', (e) => {
        const formattedPc = formatUkPostcode(ukPostcodeInput?.value || '');
        if (!isValidUkPostcode(formattedPc)) {
            e.preventDefault();
            showPostcodeError('Please enter a valid UK postcode (e.g. E1 0BJ).');
            ukPostcodeInput?.focus();
            return;
        }
        ukPostcodeInput.value = formattedPc;

        const isManual = inputIsManual?.value === '1';
        const chosenAddress = isManual
            ? (manualAddressInput?.value || '').trim()
            : (selectedAddressInput?.value || '').trim();

        if (chosenAddress.length < 5) {
            e.preventDefault();
            if (isManual && manualAddressError) {
                manualAddressError.textContent = 'Please enter your complete street address.';
                manualAddressError.classList.remove('d-none');
                manualAddressInput?.focus();
            } else if (addressSelectError) {
                addressSelectError.textContent = 'Please select your address from the dropdown or click "My address isn\'t listed".';
                addressSelectError.classList.remove('d-none');
            }
            return;
        }

        const currentAddrType = (addressTypeInput?.value || '').toUpperCase();
        if (currentAddrType === 'HOTEL') {
            const roomVal = (postcodeRoomNumberInput?.value || '').trim();
            if (!isValidRoomNumber(roomVal)) {
                e.preventDefault();
                if (postcodeRoomNumberError) {
                    postcodeRoomNumberError.textContent = !roomVal
                        ? 'Please enter your hotel room number.'
                        : 'Room number may only contain letters, numbers, hyphens, and spaces.';
                    postcodeRoomNumberError.classList.remove('d-none');
                }
                postcodeRoomNumberInput?.classList.add('is-invalid');
                postcodeRoomNumberInput?.focus();
            }
        }
    });

    // =========================================================================
    // SCREEN 3: Hotel Search & Geolocation ("Show hotels near me")
    // =========================================================================
    const btnManualHotelEntry = document.querySelector('#btnManualHotelEntry');

    const renderHotelResults = (hotels, emptyMessage = '') => {
        if (!hotelResultsList || !hotelResultsContainer) return;
        hotelResultsList.innerHTML = '';

        if (!Array.isArray(hotels) || hotels.length === 0) {
            if (emptyMessage) {
                hotelResultsContainer.classList.remove('d-none');
                const li = document.createElement('li');
                li.className = 'address-option-empty';
                li.textContent = emptyMessage;
                hotelResultsList.appendChild(li);
            } else {
                hotelResultsContainer.classList.add('d-none');
            }
            return;
        }

        hotelResultsContainer.classList.remove('d-none');

        const selectedId = String(hotelIdInput?.value || '');

        hotels.forEach(hotel => {
            const li = document.createElement('li');
            const isSelected = selectedId && hotel.id && String(hotel.id) === selectedId;
            li.className = 'hotel-result-item d-flex align-items-start gap-2' + (isSelected ? ' is-selected' : '');
            li.setAttribute('role', 'option');
            li.setAttribute('tabindex', '0');
            li.setAttribute('aria-selected', isSelected ? 'true' : 'false');

            const iconSpan = document.createElement('span');
            iconSpan.className = 'hotel-result-icon mt-1 flex-shrink-0';
            iconSpan.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1"><rect x="4" y="2" width="16" height="20" rx="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M8 10h.01M16 10h.01"></path></svg>';

            const textWrap = document.createElement('div');
            textWrap.className = 'min-w-0 flex-grow-1';

            const nameDiv = document.createElement('div');
            nameDiv.className = 'hotel-result-name fw-bold';
            nameDiv.textContent = hotel.name;

            const addrDiv = document.createElement('div');
            addrDiv.className = 'hotel-result-addr text-muted small';
            addrDiv.textContent = hotel.distance_km !== undefined && hotel.distance_km !== null
                ? `${hotel.address} (${hotel.distance_km} km)`
                : hotel.address;

            textWrap.appendChild(nameDiv);
            textWrap.appendChild(addrDiv);
            li.appendChild(iconSpan);
            li.appendChild(textWrap);

            const selectHotelItem = () => {
                if (hotelIdInput) hotelIdInput.value = hotel.id ? String(hotel.id) : '';
                if (hotelSearchInput) hotelSearchInput.value = hotel.name;
                if (hotelAddressInput) hotelAddressInput.value = hotel.address;
                hotelResultsList.querySelectorAll('.hotel-result-item').forEach(el => {
                    el.classList.remove('is-selected');
                    el.setAttribute('aria-selected', 'false');
                });
                li.classList.add('is-selected');
                li.setAttribute('aria-selected', 'true');
                hotelResultsContainer.classList.add('d-none');
                hotelNameError?.classList.add('d-none');
                hotelAddressError?.classList.add('d-none');
                validateHotelFormState();
                hotelRoomNumberInput?.focus();
            };

            li.addEventListener('click', selectHotelItem);
            li.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectHotelItem();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    li.nextElementSibling?.focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    (li.previousElementSibling || hotelSearchInput)?.focus();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    hotelResultsContainer.classList.add('d-none');
                    hotelSearchInput?.focus();
                }
            });

            hotelResultsList.appendChild(li);
        });
    };

    btnManualHotelEntry?.addEventListener('click', () => {
        if (hotelIdInput) hotelIdInput.value = '';
        hotelResultsContainer?.classList.add('d-none');
        hotelAddressInput?.focus();
        validateHotelFormState();
    });

    const searchHotelsRequest = async ({ q = '', lat = null, lon = null } = {}) => {
        hotelSearchController?.abort();
        hotelSearchController = new AbortController();

        hotelSearchSpinner?.classList.remove('d-none');

        try {
            const url = new URL(hotelSearchUrl, window.location.origin);
            if (q) url.searchParams.set('q', q);
            if (lat !== null && lon !== null) {
                url.searchParams.set('lat', String(lat));
                url.searchParams.set('lon', String(lon));
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: hotelSearchController.signal,
            });

            const data = await response.json();
            const list = Array.isArray(data.hotels) ? data.hotels : [];

            if (list.length > 0) {
                renderHotelResults(list);
            } else {
                renderHotelResults([], data.message || 'No matching hotels found in directory. You can type your hotel name and address below.');
            }
        } catch (err) {
            if (err.name === 'AbortError') return;
            renderHotelResults([], 'Hotel directory lookup unavailable. Please enter your hotel name and address manually below.');
        } finally {
            hotelSearchSpinner?.classList.add('d-none');
        }
    };

    hotelSearchInput?.addEventListener('input', () => {
        // Clear previously selected hotel ID when user modifies search text
        if (hotelIdInput) hotelIdInput.value = '';
        hotelNameError?.classList.add('d-none');
        validateHotelFormState();

        clearTimeout(hotelDebounce);
        const query = hotelSearchInput.value.trim();
        if (query.length < 2) {
            hotelResultsContainer?.classList.add('d-none');
            return;
        }
        hotelDebounce = setTimeout(() => {
            searchHotelsRequest({ q: query });
        }, 300);
    });

    hotelSearchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown' && hotelResultsContainer && !hotelResultsContainer.classList.contains('d-none')) {
            const firstItem = hotelResultsList?.querySelector('.hotel-result-item');
            if (firstItem) {
                e.preventDefault();
                firstItem.focus();
            }
        }
    });

    hotelAddressInput?.addEventListener('input', () => {
        hotelAddressError?.classList.add('d-none');
        validateHotelFormState();
    });

    // Geolocation only requested when user explicitly clicks "Show hotels near me"
    btnHotelsNearMe?.addEventListener('click', () => {
        if (!hotelGeoStatus) return;
        hotelGeoStatus.classList.remove('d-none');

        if (!navigator.geolocation) {
            hotelGeoStatus.textContent = 'Geolocation is not supported by your browser. Please type your hotel name and address.';
            return;
        }

        hotelGeoStatus.textContent = 'Requesting location permission to find nearby hotels...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                hotelGeoStatus.textContent = 'Searching for hotels near your location...';
                searchHotelsRequest({
                    q: hotelSearchInput?.value.trim() || '',
                    lat: position.coords.latitude,
                    lon: position.coords.longitude,
                }).then(() => {
                    hotelGeoStatus.textContent = 'Showing hotels near your current location.';
                });
            },
            (error) => {
                if (error.code === error.PERMISSION_DENIED) {
                    hotelGeoStatus.textContent = 'Location permission was declined. Please type your hotel name and address manually.';
                } else {
                    hotelGeoStatus.textContent = 'Could not determine your location. Please enter your hotel name and address below.';
                }
            },
            { timeout: 8000, maximumAge: 60000 }
        );
    });

    const getSelectedHotelCollectionType = () => {
        const checked = document.querySelector('.js-hotel-collection-radio:checked');
        return checked ? checked.value : '';
    };

    const setHotelCollectionType = (val) => {
        hotelCollectionRadios.forEach(radio => {
            radio.checked = radio.value === val;
        });
        hotelCollectionCards.forEach(card => {
            const isMatch = card.dataset.hotelCollectionCard === val;
            card.classList.toggle('is-selected', isMatch);
            card.setAttribute('aria-checked', isMatch ? 'true' : 'false');
        });
        if (val) {
            hotelCollectionTypeError?.classList.add('d-none');
        }
        validateHotelFormState();
    };

    hotelCollectionRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.checked) {
                setHotelCollectionType(radio.value);
            }
        });
    });

    hotelCollectionCards.forEach(card => {
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'radio');
        const initialVal = card.dataset.hotelCollectionCard;
        const radio = card.querySelector('.js-hotel-collection-radio');
        card.setAttribute('aria-checked', radio?.checked ? 'true' : 'false');

        card.addEventListener('click', () => {
            setHotelCollectionType(initialVal);
        });
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                setHotelCollectionType(initialVal);
            }
        });
    });

    function validateHotelFormState() {
        if (!btnHotelNext) return;
        const nameVal = (hotelSearchInput?.value || '').trim();
        const addrVal = (hotelAddressInput?.value || '').trim();
        const hasHotelAddress = nameVal.length >= 2 && addrVal.length >= 5;

        // Only reveal "Select collection type" after a valid hotel name & address are selected or entered
        if (hotelCollectionTypeSection) {
            hotelCollectionTypeSection.classList.toggle('d-none', !hasHotelAddress);
        }

        const roomVal = (hotelRoomNumberInput?.value || '').trim();
        const hasValidRoom = isValidRoomNumber(roomVal);
        const collectionType = getSelectedHotelCollectionType();
        const hasCollectionType = ['use_reception', 'meet_driver'].includes(collectionType);

        btnHotelNext.disabled = !(hasHotelAddress && hasValidRoom && hasCollectionType);
    }

    formHotel?.addEventListener('submit', (e) => {
        const nameVal = (hotelSearchInput?.value || '').trim();
        const addrVal = (hotelAddressInput?.value || '').trim();
        const roomVal = (hotelRoomNumberInput?.value || '').trim();
        const collectionType = getSelectedHotelCollectionType();

        if (nameVal.length < 2) {
            e.preventDefault();
            if (hotelNameError) {
                hotelNameError.textContent = 'Please enter your hotel name.';
                hotelNameError.classList.remove('d-none');
            }
            hotelSearchInput?.focus();
            return;
        }

        if (addrVal.length < 5) {
            e.preventDefault();
            if (hotelAddressError) {
                hotelAddressError.textContent = 'Please enter your hotel street address and postcode.';
                hotelAddressError.classList.remove('d-none');
            }
            hotelAddressInput?.focus();
            return;
        }

        if (!isValidRoomNumber(roomVal)) {
            e.preventDefault();
            if (hotelRoomNumberError) {
                hotelRoomNumberError.textContent = !roomVal
                    ? 'Please enter your hotel room number.'
                    : 'Room number may only contain letters, numbers, hyphens, and spaces.';
                hotelRoomNumberError.classList.remove('d-none');
            }
            hotelRoomNumberInput?.classList.add('is-invalid');
            hotelRoomNumberInput?.focus();
            return;
        }

        if (!['use_reception', 'meet_driver'].includes(collectionType)) {
            e.preventDefault();
            hotelCollectionTypeError?.classList.remove('d-none');
        }
    });

    // Close custom dropdown if clicking outside widget
    document.addEventListener('click', (e) => {
        const widget = document.querySelector('#addressSelectWidget');
        if (widget && !widget.contains(e.target)) {
            toggleAddressDropdown(false);
        }
    });

    // Initial validation check on page load
    validatePostcodeFormState();
    validateHotelFormState();
})();
