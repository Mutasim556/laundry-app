(() => {
    'use strict';

    const app = document.querySelector('#bookingServicesApp');
    if (!app) return;

    const form = document.querySelector('#formBookingServices');
    const btnServicesNext = document.querySelector('#btnServicesNext');
    const servicesSelectionError = document.querySelector('#servicesSelectionError');

    const serviceCards = document.querySelectorAll('[data-service-card]');

    /**
     * Independent frontend state cache keyed by service card ID.
     * Preserves each service's special_request, selected database wash_option_id (for Wash & Fold),
     * and textarea open/closed state even when deselected and reselected before submission.
     */
    const serviceStateCache = new Map();

    // Initialize state cache from server-rendered DOM values
    serviceCards.forEach(card => {
        const sid = String(card.dataset.serviceId);
        const checkbox = card.querySelector('.js-service-checkbox');
        const textarea = card.querySelector('.js-special-request-input');
        const requestWrap = card.querySelector(`[data-request-wrap="${sid}"]`);
        const checkedRadio = card.querySelector('.js-wash-option-radio:checked');

        serviceStateCache.set(sid, {
            selected: Boolean(checkbox?.checked),
            wash_option_id: checkedRadio ? checkedRadio.value : null,
            special_request: textarea ? textarea.value : '',
            textarea_open: requestWrap ? !requestWrap.classList.contains('d-none') : false,
        });
    });

    /**
     * Update Wash & Fold card subtitle dynamically when a database wash option is chosen
     * (Displays the chosen database record name and price, e.g. "Mixed Wash & Fold added (BDT 600.00 / kg)")
     */
    const updateCardSubtitle = (card, sid) => {
        const subtitleEl = card.querySelector(`[data-service-subtitle="${sid}"]`);
        if (!subtitleEl) return;

        const isWashFold = card.dataset.isWashFold === '1';
        const state = serviceStateCache.get(sid);

        if (isWashFold && state?.selected && state?.wash_option_id) {
            const radio = card.querySelector(`.js-wash-option-radio[value="${state.wash_option_id}"]`);
            if (radio) {
                const optName = radio.dataset.optionName || 'Wash & Fold';
                const optPrice = radio.dataset.optionPrice || '';
                const optUnit = radio.dataset.optionUnit || 'kg';
                subtitleEl.textContent = optPrice
                    ? `${optName} added (BDT ${optPrice} / ${optUnit})`
                    : `${optName} added`;
                return;
            }
        }

        subtitleEl.textContent = subtitleEl.dataset.defaultSubtitle || '';
    };

    /**
     * Toggle selection state of a service card.
     * - When selected: highlights card in yellow, expands options panel, restores any previously typed special_request.
     * - When deselected: collapses panel, disables inputs so deselected service's data is NOT submitted,
     *   while preserving the entered special_request and wash_option_id in serviceStateCache.
     */
    const setServiceSelected = (card, isSelected) => {
        const sid = String(card.dataset.serviceId);
        const checkbox = card.querySelector('.js-service-checkbox');
        const toggleRow = card.querySelector(`[data-service-toggle="${sid}"]`);
        const expandPanel = card.querySelector(`[data-expand-panel="${sid}"]`);
        const textarea = card.querySelector('.js-special-request-input');
        const requestWrap = card.querySelector(`[data-request-wrap="${sid}"]`);
        const toggleBtn = card.querySelector(`[data-toggle-request="${sid}"]`);
        const toggleLabel = card.querySelector(`[data-toggle-label="${sid}"]`);
        const washRadios = card.querySelectorAll('.js-wash-option-radio');
        const charCounter = card.querySelector(`[data-char-count="${sid}"]`);

        let state = serviceStateCache.get(sid) || {
            selected: false,
            wash_option_id: null,
            special_request: '',
            textarea_open: false,
        };

        if (!isSelected) {
            // Save current values into frontend cache before collapsing
            if (textarea) {
                state.special_request = textarea.value;
            }
            const checkedRadio = card.querySelector('.js-wash-option-radio:checked');
            if (checkedRadio) {
                state.wash_option_id = checkedRadio.value;
            }
            state.selected = false;
            serviceStateCache.set(sid, state);

            // Collapse card & disable child inputs so deselected service instructions are NOT submitted
            card.classList.remove('is-selected');
            if (checkbox) checkbox.checked = false;
            toggleRow?.setAttribute('aria-checked', 'false');
            expandPanel?.classList.add('d-none');

            if (textarea) textarea.disabled = true;
            washRadios.forEach(r => {
                r.disabled = true;
            });
        } else {
            state.selected = true;
            serviceStateCache.set(sid, state);

            card.classList.add('is-selected');
            if (checkbox) checkbox.checked = true;
            toggleRow?.setAttribute('aria-checked', 'true');
            expandPanel?.classList.remove('d-none');

            // Re-enable inputs and restore previously entered special request & wash option from cache
            if (textarea) {
                textarea.disabled = false;
                textarea.value = state.special_request || '';
                if (charCounter) {
                    charCounter.textContent = String(textarea.value.length);
                }
            }

            washRadios.forEach(r => {
                r.disabled = false;
                const isMatch = String(state.wash_option_id) === String(r.value);
                r.checked = isMatch;
                r.closest('.wash-type-radio-card')?.classList.toggle('is-checked', isMatch);
            });

            // Restore textarea visibility if user had it open or had typed instructions
            const shouldOpenTextarea = state.textarea_open || (state.special_request && state.special_request.trim().length > 0);
            if (requestWrap && toggleBtn && toggleLabel) {
                requestWrap.classList.toggle('d-none', !shouldOpenTextarea);
                toggleBtn.setAttribute('aria-expanded', shouldOpenTextarea ? 'true' : 'false');
                toggleLabel.textContent = shouldOpenTextarea ? 'Hide Special Request' : 'Add Special Request';
            }
        }

        updateCardSubtitle(card, sid);
        servicesSelectionError?.classList.add('d-none');
        validateNextButtonState();
    };

    /**
     * Toggle "Add Special Request" / "Hide Special Request" for a specific service card.
     * Hiding the textarea preserves the typed text both in the textarea and in serviceStateCache.
     */
    const toggleSpecialRequestTextarea = (card) => {
        const sid = String(card.dataset.serviceId);
        const requestWrap = card.querySelector(`[data-request-wrap="${sid}"]`);
        const toggleBtn = card.querySelector(`[data-toggle-request="${sid}"]`);
        const toggleLabel = card.querySelector(`[data-toggle-label="${sid}"]`);
        const textarea = card.querySelector('.js-special-request-input');

        if (!requestWrap || !toggleBtn || !toggleLabel) return;

        const isCurrentlyHidden = requestWrap.classList.contains('d-none');
        const nextOpen = isCurrentlyHidden;

        requestWrap.classList.toggle('d-none', !nextOpen);
        toggleBtn.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        toggleLabel.textContent = nextOpen ? 'Hide Special Request' : 'Add Special Request';

        const state = serviceStateCache.get(sid) || {};
        state.textarea_open = nextOpen;
        if (textarea) {
            state.special_request = textarea.value;
        }
        serviceStateCache.set(sid, state);

        if (nextOpen && textarea && !textarea.disabled) {
            textarea.focus();
        }
    };

    /**
     * Validate whether NEXT button should be enabled:
     * 1. At least one service is selected.
     * 2. If Wash & Fold is selected, one of its database options (Mixed Wash & Fold or Separate Wash & Fold) must be selected.
     * 3. Special requests are completely optional for all services.
     */
    function validateNextButtonState() {
        if (!btnServicesNext) return;

        let selectedCount = 0;
        let washFoldValid = true;

        serviceCards.forEach(card => {
            const checkbox = card.querySelector('.js-service-checkbox');
            const isWashFold = card.dataset.isWashFold === '1';

            if (checkbox?.checked) {
                selectedCount++;
                if (isWashFold) {
                    const checkedWashOption = card.querySelector('.js-wash-option-radio:checked');
                    if (!checkedWashOption) {
                        washFoldValid = false;
                    }
                }
            }
        });

        btnServicesNext.disabled = !(selectedCount > 0 && washFoldValid);
    }

    // Bind events on each service card
    serviceCards.forEach(card => {
        const sid = String(card.dataset.serviceId);
        const toggleRow = card.querySelector(`[data-service-toggle="${sid}"]`);
        const toggleRequestBtn = card.querySelector(`[data-toggle-request="${sid}"]`);
        const textarea = card.querySelector('.js-special-request-input');
        const charCounter = card.querySelector(`[data-char-count="${sid}"]`);
        const washRadios = card.querySelectorAll('.js-wash-option-radio');
        const washError = card.querySelector(`[data-wash-type-error="${sid}"]`);

        // Clicking card header toggles selection (unless clicking "Prices & more" link)
        toggleRow?.addEventListener('click', (e) => {
            if (e.target.closest('.js-prices-more-link')) {
                return;
            }
            const checkbox = card.querySelector('.js-service-checkbox');
            setServiceSelected(card, !checkbox?.checked);
        });

        toggleRow?.addEventListener('keydown', (e) => {
            if (e.target.closest('.js-prices-more-link')) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const checkbox = card.querySelector('.js-service-checkbox');
                setServiceSelected(card, !checkbox?.checked);
            }
        });

        // Clicking "Add Special Request" / "Hide Special Request"
        toggleRequestBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSpecialRequestTextarea(card);
        });

        // Typing in the independent special request textarea
        textarea?.addEventListener('input', () => {
            const val = textarea.value;
            if (charCounter) {
                charCounter.textContent = String(val.length);
            }
            const state = serviceStateCache.get(sid) || {};
            state.special_request = val;
            serviceStateCache.set(sid, state);
        });

        // Selecting Mixed Wash & Fold or Separate Wash & Fold database option radio button
        washRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                washRadios.forEach(r => {
                    r.closest('.wash-type-radio-card')?.classList.toggle('is-checked', r.checked);
                });

                const state = serviceStateCache.get(sid) || {};
                if (radio.checked) {
                    state.wash_option_id = radio.value;
                }
                serviceStateCache.set(sid, state);

                washError?.classList.add('d-none');
                updateCardSubtitle(card, sid);
                validateNextButtonState();
            });
        });
    });

    // Form submission validation
    form?.addEventListener('submit', (e) => {
        let selectedCount = 0;
        let firstInvalidWashFoldCard = null;

        serviceCards.forEach(card => {
            const sid = String(card.dataset.serviceId);
            const checkbox = card.querySelector('.js-service-checkbox');
            const isWashFold = card.dataset.isWashFold === '1';
            const washError = card.querySelector(`[data-wash-type-error="${sid}"]`);

            if (checkbox?.checked) {
                selectedCount++;
                if (isWashFold) {
                    const checkedRadio = card.querySelector('.js-wash-option-radio:checked');
                    if (!checkedRadio) {
                        washError?.classList.remove('d-none');
                        if (!firstInvalidWashFoldCard) {
                            firstInvalidWashFoldCard = card;
                        }
                    } else {
                        washError?.classList.add('d-none');
                    }
                }
            }
        });

        if (selectedCount === 0) {
            e.preventDefault();
            if (servicesSelectionError) {
                servicesSelectionError.textContent = 'Please select at least one service to continue.';
                servicesSelectionError.classList.remove('d-none');
            }
            return;
        }

        if (firstInvalidWashFoldCard) {
            e.preventDefault();
            firstInvalidWashFoldCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    // Initial check on page load
    validateNextButtonState();
})();
