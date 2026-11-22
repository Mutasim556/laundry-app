(() => {
    'use strict';

    const app = document.querySelector('#bookingConfirmApp');
    if (!app) return;

    const optionsUrl = app.dataset.optionsUrl || '/booking/confirm/options';
    const currency = app.dataset.currency || 'BDT';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Price display elements
    const priceDriverTipRow = document.querySelector('#priceDriverTipRow');
    const priceDriverTipValue = document.querySelector('#priceDriverTipValue');
    const priceEstimatedTotalValue = document.querySelector('#priceEstimatedTotalValue');
    const priceDueTodayValue = document.querySelector('#priceDueTodayValue');

    // Promo Code elements
    const promoCodeInput = document.querySelector('#promoCodeInput');
    const btnApplyPromoCode = document.querySelector('#btnApplyPromoCode');
    const promoCodeFeedback = document.querySelector('#promoCodeFeedback');

    // "How charges work" toggle
    const btnToggleHowChargesWork = document.querySelector('#btnToggleHowChargesWork');
    const howChargesWorkCollapse = document.querySelector('#howChargesWorkCollapse');

    // Driver Tip elements
    const tipPresetBtns = document.querySelectorAll('.rc-tip-btn');
    const btnCustomTipToggle = document.querySelector('#btnCustomTipToggle');
    const customTipInputWrap = document.querySelector('#customTipInputWrap');
    const customTipInput = document.querySelector('#customTipInput');
    const btnApplyCustomTip = document.querySelector('#btnApplyCustomTip');
    const customTipError = document.querySelector('#customTipError');

    // Subscribe & Repeat Schedule elements
    const repeatCards = document.querySelectorAll('[data-repeat-card]');
    const repeatRadios = document.querySelectorAll('.js-confirm-repeat-radio');

    // Payment Method elements
    const btnTogglePaymentSelector = document.querySelector('#btnTogglePaymentSelector');
    const paymentMethodSelectorPanel = document.querySelector('#paymentMethodSelectorPanel');
    const selectedPaymentDisplay = document.querySelector('#selectedPaymentDisplay');
    const paymentCards = document.querySelectorAll('[data-payment-card]');
    const paymentRadios = document.querySelectorAll('.js-payment-method-radio');
    const paymentMethodError = document.querySelector('#paymentMethodError');

    // Final Submit Form & Hidden Inputs
    const formBookingConfirm = document.querySelector('#formBookingConfirm');
    const formInputPaymentMethod = document.querySelector('#formInputPaymentMethod');
    const formInputTipOption = document.querySelector('#formInputTipOption');
    const formInputCustomTipAmount = document.querySelector('#formInputCustomTipAmount');
    const formInputRepeatFrequency = document.querySelector('#formInputRepeatFrequency');
    const btnConfirmBooking = document.querySelector('#btnConfirmBooking');
    const btnConfirmBookingText = document.querySelector('#btnConfirmBookingText');
    const btnConfirmBookingSpinner = document.querySelector('#btnConfirmBookingSpinner');

    const formatMoney = (amount) => {
        const num = Number(amount || 0);
        return `${currency} ${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    const recalculateLocalTotals = (tipAmount) => {
        const minOrder = parseFloat(app.dataset.minOrder || '150');
        const servicesSub = parseFloat(app.dataset.servicesSubtotal || '0');
        const serviceFee = parseFloat(app.dataset.serviceFee || '0');
        const deliveryFee = parseFloat(app.dataset.deliveryFee || '0');
        const safeTip = Math.max(0, parseFloat(tipAmount || '0') || 0);

        if (priceDriverTipRow && priceDriverTipValue) {
            priceDriverTipRow.classList.toggle('d-none', safeTip <= 0);
            priceDriverTipValue.textContent = formatMoney(safeTip);
        }

        const estimatedTotal = Math.max(minOrder, servicesSub) + serviceFee + deliveryFee + safeTip;
        if (priceEstimatedTotalValue) {
            priceEstimatedTotalValue.textContent = formatMoney(estimatedTotal);
        }
    };

    const syncOptionsToServer = async (payload) => {
        try {
            const response = await fetch(optionsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (data.pricing) {
                const p = data.pricing;
                if (priceDriverTipRow && priceDriverTipValue) {
                    priceDriverTipRow.classList.toggle('d-none', !(p.tip_amount > 0));
                    priceDriverTipValue.textContent = formatMoney(p.tip_amount);
                }
                if (priceEstimatedTotalValue) {
                    priceEstimatedTotalValue.textContent = formatMoney(p.estimated_total);
                }
                if (priceDueTodayValue) {
                    priceDueTodayValue.textContent = formatMoney(p.due_today);
                }
            }

            return { ok: response.ok, data };
        } catch (err) {
            return { ok: false, data: null };
        }
    };

    // =========================================================================
    // 1. Promo Code Validation
    // =========================================================================
    promoCodeInput?.addEventListener('input', () => {
        promoCodeInput.value = promoCodeInput.value.toUpperCase();
        promoCodeFeedback?.classList.add('d-none');
        promoCodeInput.classList.remove('is-invalid');
    });

    const handleApplyPromoCode = async () => {
        if (!promoCodeInput || !promoCodeFeedback) return;
        const code = promoCodeInput.value.trim().toUpperCase();

        if (!code) {
            promoCodeFeedback.textContent = 'Please enter a promo code to apply.';
            promoCodeFeedback.classList.remove('d-none');
            promoCodeInput.classList.add('is-invalid');
            promoCodeInput.focus();
            return;
        }

        if (btnApplyPromoCode) btnApplyPromoCode.disabled = true;

        const { ok, data } = await syncOptionsToServer({
            action: 'apply_promo',
            promo_code: code,
        });

        if (btnApplyPromoCode) btnApplyPromoCode.disabled = false;

        if (!ok || !data?.success) {
            promoCodeFeedback.textContent = data?.message || `Promo code "${code}" is invalid or expired.`;
            promoCodeFeedback.classList.remove('d-none');
            promoCodeInput.classList.add('is-invalid');
        } else {
            promoCodeFeedback.classList.add('d-none');
            promoCodeInput.classList.remove('is-invalid');
        }
    };

    btnApplyPromoCode?.addEventListener('click', handleApplyPromoCode);
    promoCodeInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleApplyPromoCode();
        }
    });

    // =========================================================================
    // 2. "How charges work" Info Accordion
    // =========================================================================
    btnToggleHowChargesWork?.addEventListener('click', () => {
        if (!howChargesWorkCollapse) return;
        const isHidden = howChargesWorkCollapse.classList.contains('d-none');
        howChargesWorkCollapse.classList.toggle('d-none', !isHidden);
        btnToggleHowChargesWork.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });

    // =========================================================================
    // 3. Driver Tip Selection (4 Presets + Custom Tip)
    // =========================================================================
    const selectTipOption = (optionKey, customAmountVal = null) => {
        customTipError?.classList.add('d-none');
        customTipInput?.classList.remove('is-invalid');

        // Highlight active preset button
        tipPresetBtns.forEach(btn => {
            const match = btn.dataset.tipOption === String(optionKey);
            btn.classList.toggle('is-selected', match);
            btn.setAttribute('aria-checked', match ? 'true' : 'false');
        });

        const isCustom = optionKey === 'custom';
        if (btnCustomTipToggle) {
            btnCustomTipToggle.classList.toggle('is-selected', isCustom);
            btnCustomTipToggle.setAttribute('aria-checked', isCustom ? 'true' : 'false');
        }
        if (customTipInputWrap) {
            customTipInputWrap.classList.toggle('d-none', !isCustom);
        }

        let tipAmount = 0;
        if (isCustom) {
            const rawVal = customAmountVal !== null ? customAmountVal : (customTipInput?.value || '0');
            const parsed = parseFloat(rawVal);
            if (isNaN(parsed) || parsed < 0) {
                if (customTipError) {
                    customTipError.textContent = 'Please enter a valid non-negative tip amount.';
                    customTipError.classList.remove('d-none');
                }
                customTipInput?.classList.add('is-invalid');
                return;
            }
            if (parsed > 5000) {
                if (customTipError) {
                    customTipError.textContent = `Maximum allowed driver tip is ${currency} 5,000.00.`;
                    customTipError.classList.remove('d-none');
                }
                customTipInput?.classList.add('is-invalid');
                return;
            }
            tipAmount = Math.round(parsed * 100) / 100;
        } else {
            tipAmount = parseFloat(optionKey) || 0;
        }

        if (formInputTipOption) formInputTipOption.value = String(optionKey);
        if (formInputCustomTipAmount) formInputCustomTipAmount.value = String(tipAmount);

        recalculateLocalTotals(tipAmount);

        syncOptionsToServer({
            action: 'update_tip',
            tip_option: String(optionKey),
            custom_tip_amount: tipAmount,
        });
    };

    tipPresetBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            selectTipOption(btn.dataset.tipOption);
        });
    });

    btnCustomTipToggle?.addEventListener('click', () => {
        selectTipOption('custom');
        customTipInput?.focus();
    });

    btnApplyCustomTip?.addEventListener('click', () => {
        selectTipOption('custom', customTipInput?.value || '0');
    });

    customTipInput?.addEventListener('input', () => {
        customTipError?.classList.add('d-none');
        customTipInput.classList.remove('is-invalid');
        const val = parseFloat(customTipInput.value || '0');
        if (!isNaN(val) && val >= 0 && val <= 5000) {
            if (formInputCustomTipAmount) formInputCustomTipAmount.value = String(val);
            recalculateLocalTotals(val);
        }
    });

    customTipInput?.addEventListener('change', () => {
        selectTipOption('custom', customTipInput.value || '0');
    });

    // =========================================================================
    // 4. Subscribe & Repeat Schedule Selection (Synced with Step 3)
    // =========================================================================
    const selectRepeatFrequency = (freq) => {
        repeatRadios.forEach(r => {
            r.checked = r.value === freq;
        });
        repeatCards.forEach(card => {
            const match = card.dataset.repeatCard === freq;
            card.classList.toggle('is-selected', match);
        });
        if (formInputRepeatFrequency) {
            formInputRepeatFrequency.value = freq;
        }
        syncOptionsToServer({
            action: 'update_repeat',
            repeat_frequency: freq,
        });
    };

    repeatRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.checked) {
                selectRepeatFrequency(radio.value);
            }
        });
    });

    // =========================================================================
    // 5. Payment Method Selection ("Add" / "Edit")
    // =========================================================================
    btnTogglePaymentSelector?.addEventListener('click', () => {
        if (!paymentMethodSelectorPanel) return;
        const isHidden = paymentMethodSelectorPanel.classList.contains('d-none');
        paymentMethodSelectorPanel.classList.toggle('d-none', !isHidden);
        btnTogglePaymentSelector.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });

    const selectPaymentMethod = (methodKey, name, desc, badge) => {
        paymentRadios.forEach(r => {
            r.checked = r.value === methodKey;
        });
        paymentCards.forEach(card => {
            card.classList.toggle('is-selected', card.dataset.paymentCard === methodKey);
        });

        if (formInputPaymentMethod) {
            formInputPaymentMethod.value = methodKey;
        }

        if (selectedPaymentDisplay) {
            selectedPaymentDisplay.innerHTML = '';
            const wrap = document.createElement('div');
            wrap.className = 'd-flex align-items-center justify-content-between';

            const left = document.createElement('div');
            const nameDiv = document.createElement('div');
            nameDiv.className = 'fw-bold text-dark';
            nameDiv.id = 'selectedPaymentName';
            nameDiv.textContent = name;

            const descDiv = document.createElement('div');
            descDiv.className = 'small text-secondary';
            descDiv.id = 'selectedPaymentDesc';
            descDiv.textContent = desc;

            left.appendChild(nameDiv);
            left.appendChild(descDiv);

            const badgeSpan = document.createElement('span');
            badgeSpan.className = 'badge rounded-pill bg-warning text-dark fw-bold';
            badgeSpan.id = 'selectedPaymentBadge';
            badgeSpan.textContent = badge;

            wrap.appendChild(left);
            wrap.appendChild(badgeSpan);
            selectedPaymentDisplay.appendChild(wrap);
        }

        if (btnTogglePaymentSelector) {
            btnTogglePaymentSelector.textContent = 'Edit';
        }

        paymentMethodError?.classList.add('d-none');

        syncOptionsToServer({
            action: 'update_payment',
            payment_method: methodKey,
        });

        toggleStripePanel(methodKey === 'stripe');
        document.querySelector('#paypalDetailsPanel')?.classList.toggle('d-none', methodKey !== 'paypal');
        document.querySelector('#codDetailsPanel')?.classList.toggle('d-none', methodKey !== 'cod');
    };

    // =========================================================================
    // 5B. Stripe.js Payment Element & SetupIntent Flow
    // =========================================================================
    const stripeCardPanel = document.querySelector('#stripeCardPanel');
    const stripeElementLoading = document.querySelector('#stripeElementLoading');
    const stripePaymentElementContainer = document.querySelector('#stripePaymentElement');
    const stripeElementError = document.querySelector('#stripeElementError');
    const stripeSaveCardCheckbox = document.querySelector('#stripeSaveCardCheckbox');
    const stripeConsentCheckbox = document.querySelector('#stripeConsentCheckbox');
    const stripeNewCardWrap = document.querySelector('#stripeNewCardWrap');
    const savedCardRadios = document.querySelectorAll('.js-saved-card-radio');

    const formInputStripeSetupIntentId = document.querySelector('#formInputStripeSetupIntentId');
    const formInputStripePaymentMethodId = document.querySelector('#formInputStripePaymentMethodId');
    const formInputSavedPaymentMethodId = document.querySelector('#formInputSavedPaymentMethodId');
    const formInputSaveCard = document.querySelector('#formInputSaveCard');
    const formInputStripeConsent = document.querySelector('#formInputStripeConsent');

    const stripePublishableKey = app.dataset.stripeKey || '';
    const stripeConfigured = app.dataset.stripeConfigured === '1';
    const setupIntentUrl = app.dataset.setupIntentUrl || '/booking/stripe/setup-intent';

    let stripeInstance = null;
    let stripeElements = null;
    let stripeClientSecret = null;
    let stripeSetupIntentId = null;

    const initStripeElement = async () => {
        if (!stripeConfigured || !stripePublishableKey || !window.Stripe || !stripePaymentElementContainer) return;
        if (stripeElements) return;

        stripeElementLoading?.classList.remove('d-none');
        stripeElementError?.classList.add('d-none');

        try {
            const res = await fetch(setupIntentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({}),
            });
            const data = await res.json();
            if (!res.ok || !data.client_secret) {
                throw new Error(data.message || 'Unable to initialize Stripe card setup.');
            }

            stripeClientSecret = data.client_secret;
            stripeSetupIntentId = data.setup_intent_id;
            stripeInstance = window.Stripe(stripePublishableKey);
            stripeElements = stripeInstance.elements({
                clientSecret: stripeClientSecret,
                appearance: {
                    theme: 'stripe',
                    variables: {
                        colorPrimary: '#FFC629',
                        colorBackground: '#FFFFFF',
                        colorText: '#171717',
                        borderRadius: '10px',
                    },
                },
            });

            const paymentElement = stripeElements.create('payment');
            paymentElement.mount('#stripePaymentElement');
            stripeElementLoading?.classList.add('d-none');
        } catch (err) {
            stripeElementLoading?.classList.add('d-none');
            if (stripeElementError) {
                stripeElementError.textContent = err.message || 'Failed to load secure card fields.';
                stripeElementError.classList.remove('d-none');
            }
        }
    };

    const toggleStripePanel = (show) => {
        if (!stripeCardPanel) return;
        stripeCardPanel.classList.toggle('d-none', !show);
        if (show) {
            initStripeElement();
        }
    };

    if (formInputPaymentMethod?.value === 'stripe') {
        toggleStripePanel(true);
    }

    savedCardRadios.forEach(r => {
        r.addEventListener('change', () => {
            if (!r.checked) return;
            const usingSaved = r.value !== '';
            if (formInputSavedPaymentMethodId) formInputSavedPaymentMethodId.value = r.value;
            stripeNewCardWrap?.classList.toggle('d-none', usingSaved);
        });
    });

    document.querySelectorAll('.js-delete-saved-card').forEach(btn => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.deleteUrl;
            if (!url) return;
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (res.ok) {
                btn.closest('div')?.remove();
            }
        });
    });

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.checked) {
                selectPaymentMethod(
                    radio.value,
                    radio.dataset.pmName || radio.value,
                    radio.dataset.pmDesc || '',
                    radio.dataset.pmBadge || 'Selected'
                );
            }
        });
    });

    // =========================================================================
    // 6. Final Submission ("CONTINUE TO PAYMENT ->") Duplicate-Submit Guard
    // =========================================================================
    let isSubmittingConfirm = false;
    formBookingConfirm?.addEventListener('submit', async (e) => {
        if (isSubmittingConfirm) {
            e.preventDefault();
            return;
        }

        // Validate custom tip if custom option is active
        if (formInputTipOption?.value === 'custom' && customTipInput) {
            const val = parseFloat(customTipInput.value || '0');
            if (isNaN(val) || val < 0 || val > 5000) {
                e.preventDefault();
                if (customTipError) {
                    customTipError.textContent = val > 5000
                        ? `Maximum allowed driver tip is ${currency} 5,000.00.`
                        : 'Please enter a valid non-negative tip amount.';
                    customTipError.classList.remove('d-none');
                }
                customTipInput.classList.add('is-invalid');
                customTipInput.focus();
                return;
            }
            if (formInputCustomTipAmount) {
                formInputCustomTipAmount.value = String(Math.round(val * 100) / 100);
            }
        }

        // If Stripe is selected, confirm SetupIntent before submitting to Laravel
        if (formInputPaymentMethod?.value === 'stripe') {
            e.preventDefault();

            if (!stripeConsentCheckbox?.checked) {
                if (stripeElementError) {
                    stripeElementError.textContent = 'Please accept the payment authorization consent above to continue.';
                    stripeElementError.classList.remove('d-none');
                }
                stripeConsentCheckbox?.focus();
                return;
            }

            if (formInputStripeConsent) formInputStripeConsent.value = '1';
            if (formInputSaveCard) formInputSaveCard.value = stripeSaveCardCheckbox?.checked ? '1' : '0';

            const usingSavedCard = (formInputSavedPaymentMethodId?.value || '') !== '';
            isSubmittingConfirm = true;
            if (btnConfirmBooking) btnConfirmBooking.disabled = true;
            if (btnConfirmBookingText) btnConfirmBookingText.textContent = 'AUTHORIZING CARD...';
            btnConfirmBookingSpinner?.classList.remove('d-none');

            if (usingSavedCard) {
                formBookingConfirm.submit();
                return;
            }

            if (!stripeInstance || !stripeElements) {
                isSubmittingConfirm = false;
                if (btnConfirmBooking) btnConfirmBooking.disabled = false;
                if (btnConfirmBookingText) btnConfirmBookingText.textContent = 'CONTINUE TO PAYMENT →';
                btnConfirmBookingSpinner?.classList.add('d-none');
                if (stripeElementError) {
                    stripeElementError.textContent = 'Stripe card form is not ready. Please wait or select another payment method.';
                    stripeElementError.classList.remove('d-none');
                }
                return;
            }

            const { setupIntent, error } = await stripeInstance.confirmSetup({
                elements: stripeElements,
                redirect: 'if_required',
            });

            if (error) {
                isSubmittingConfirm = false;
                if (btnConfirmBooking) btnConfirmBooking.disabled = false;
                if (btnConfirmBookingText) btnConfirmBookingText.textContent = 'CONTINUE TO PAYMENT →';
                btnConfirmBookingSpinner?.classList.add('d-none');
                if (stripeElementError) {
                    stripeElementError.textContent = error.message || 'Card setup failed. Please check your details.';
                    stripeElementError.classList.remove('d-none');
                }
                return;
            }

            if (formInputStripeSetupIntentId) formInputStripeSetupIntentId.value = setupIntent?.id || stripeSetupIntentId || '';
            if (formInputStripePaymentMethodId) formInputStripePaymentMethodId.value = typeof setupIntent?.payment_method === 'string'
                ? setupIntent.payment_method
                : (setupIntent?.payment_method?.id || '');

            formBookingConfirm.submit();
            return;
        }

        isSubmittingConfirm = true;
        if (btnConfirmBooking) {
            btnConfirmBooking.disabled = true;
        }
        if (btnConfirmBookingText) {
            btnConfirmBookingText.textContent = 'PROCESSING BOOKING...';
        }
        btnConfirmBookingSpinner?.classList.remove('d-none');
    });
})();
