(() => {
    const app = document.querySelector('#bookingConfirmApp');
    if (!app) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const optionsUrl = app.dataset.optionsUrl || '';

    const paymentRadios = document.querySelectorAll('.js-payment-radio');
    const paymentCards = document.querySelectorAll('[data-payment-card]');
    const paymentMethodList = document.querySelector('#paymentMethodList');
    const paymentMethodError = document.querySelector('#paymentMethodError');

    const formBookingConfirm = document.querySelector('#formBookingConfirm');
    const formInputPaymentMethod = document.querySelector('#formInputPaymentMethod');
    const btnConfirmBooking = document.querySelector('#btnConfirmBooking');
    const btnConfirmBookingText = document.querySelector('#btnConfirmBookingText');
    const btnConfirmBookingSpinner = document.querySelector('#btnConfirmBookingSpinner');

    const syncOptionsToServer = async (payload) => {
        if (!optionsUrl) return;
        try {
            await fetch(optionsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
        } catch {
            // Non-blocking background session sync
        }
    };

    const selectPaymentMethod = (methodKey) => {
        paymentRadios.forEach((r) => {
            r.checked = r.value === methodKey;
        });
        paymentCards.forEach((card) => {
            card.classList.toggle('is-selected', card.dataset.paymentCard === methodKey);
        });

        if (formInputPaymentMethod) {
            formInputPaymentMethod.value = methodKey;
        }

        paymentMethodError?.classList.add('d-none');

        document.querySelector('#stripeCardPanel')?.classList.toggle('d-none', methodKey !== 'stripe');
        document.querySelector('#paypalDetailsPanel')?.classList.toggle('d-none', methodKey !== 'paypal');
        document.querySelector('#codDetailsPanel')?.classList.toggle('d-none', methodKey !== 'cod');

        syncOptionsToServer({
            action: 'update_payment',
            payment_method: methodKey,
        });
    };

    const initiallyChecked = document.querySelector('.js-payment-radio:checked');
    if (initiallyChecked) {
        selectPaymentMethod(initiallyChecked.value);
    }

    paymentRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            if (radio.checked) {
                selectPaymentMethod(radio.value);
            }
        });
    });

    // Final Submission ("Confirm Booking") with Validation & Duplicate-Submit Guard
    let isSubmittingConfirm = false;
    formBookingConfirm?.addEventListener('submit', (e) => {
        if (isSubmittingConfirm) {
            e.preventDefault();
            return;
        }

        const checkedRadio = document.querySelector('.js-payment-radio:checked');
        const selectedMethod = (checkedRadio?.value || formInputPaymentMethod?.value || '').trim();
        if (formInputPaymentMethod && selectedMethod) {
            formInputPaymentMethod.value = selectedMethod;
        }

        if (!selectedMethod) {
            e.preventDefault();
            if (paymentMethodError) {
                paymentMethodError.textContent = 'Please select a payment method to continue.';
                paymentMethodError.classList.remove('d-none');
            }
            paymentMethodList?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        isSubmittingConfirm = true;
        if (btnConfirmBooking) btnConfirmBooking.disabled = true;
        if (btnConfirmBookingText) btnConfirmBookingText.textContent = 'CONFIRMING BOOKING...';
        btnConfirmBookingSpinner?.classList.remove('d-none');
    });
})();
