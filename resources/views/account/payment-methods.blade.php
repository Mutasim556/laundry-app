<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Saved Payment Methods | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
</head>
<body class="booking-page-body">
    
    @include('shared.header')

    <main class="container pt-4 pt-md-5" style="padding-bottom: calc(116px + env(safe-area-inset-bottom, 0px));">
        <div class="booking-confirm-shell mx-auto" style="max-width: 820px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h1 class="booking-screen-title mb-1">Saved Payment Methods</h1>
                    <p class="booking-screen-subtitle mb-0">Manage your saved cards securely powered by Stripe.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success small rounded-3">{{ session('success') }}</div>
            @endif

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                        <div class="card-body p-4">
                            <h2 class="h6 fw-bold text-dark mb-3">Your Saved Cards</h2>
                            @forelse ($cards as $card)
                                <div class="border rounded-4 p-3 mb-2 {{ $card->is_default ? 'border-warning bg-light' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-bold text-dark small">
                                                {{ strtoupper($card->card_brand) }} &bull;&bull;&bull;&bull; {{ $card->card_last4 }}
                                            </div>
                                            <div class="small text-muted">
                                                Expires {{ str_pad((string) $card->card_exp_month, 2, '0', STR_PAD_LEFT) }}/{{ $card->card_exp_year }}
                                            </div>
                                        </div>
                                        @if ($card->is_default)
                                            <span class="badge bg-warning text-dark rounded-pill">Default</span>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        @if (!$card->is_default)
                                            <form method="POST" action="{{ route('account.payment-methods.default', $card->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill">Set Default</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('account.payment-methods.destroy', $card->id) }}"
                                              onsubmit="return confirm('Remove this saved card?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-secondary small mb-0">You have no saved cards yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                        <div class="card-body p-4">
                            <h2 class="h6 fw-bold text-dark mb-3">Add a New Card</h2>
                            @if (!$stripeActive || empty($stripePublishableKey))
                                <div class="alert alert-warning small rounded-3 mb-0">
                                    Stripe saved card setup is currently unavailable. Please contact support or use another payment method at checkout.
                                </div>
                            @else
                                <div id="pmStripeElement"></div>
                                <div id="pmStripeError" class="text-danger small fw-semibold mt-2 d-none"></div>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="pmSaveConsent">
                                    <label class="form-check-label small text-dark" for="pmSaveConsent">
                                        I authorize FreshGo Laundry &amp; Stripe to securely save this card for future laundry orders.
                                    </label>
                                </div>
                                <button type="button" id="btnAddCardStripe" class="btn btn-warning w-100 rounded-pill py-2 fw-bold text-dark mt-3">
                                    Save Card Securely
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('shared.footer')
    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    @if ($stripeActive && !empty($stripePublishableKey))
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', async () => {
                const errEl = document.getElementById('pmStripeError');
                const btn = document.getElementById('btnAddCardStripe');
                const consent = document.getElementById('pmSaveConsent');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const res = await fetch(@json(route('account.payment-methods.setup-intent')), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json();
                if (!res.ok || !data.client_secret) {
                    errEl.textContent = data.message || 'Unable to initialize card setup.';
                    errEl.classList.remove('d-none');
                    return;
                }

                const stripe = Stripe(@json($stripePublishableKey));
                const elements = stripe.elements({ clientSecret: data.client_secret });
                elements.create('payment').mount('#pmStripeElement');

                btn.addEventListener('click', async () => {
                    errEl.classList.add('d-none');
                    if (!consent.checked) {
                        errEl.textContent = 'Please accept the card authorization consent.';
                        errEl.classList.remove('d-none');
                        return;
                    }
                    btn.disabled = true;
                    const { setupIntent, error } = await stripe.confirmSetup({ elements, redirect: 'if_required' });
                    if (error) {
                        errEl.textContent = error.message || 'Card setup failed.';
                        errEl.classList.remove('d-none');
                        btn.disabled = false;
                        return;
                    }
                    const attachRes = await fetch(@json(route('account.payment-methods.attach')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({
                            stripe_payment_method_id: setupIntent.payment_method,
                            consent: true,
                        }),
                    });
                    if (attachRes.ok) {
                        window.location.reload();
                    } else {
                        errEl.textContent = 'Unable to save card metadata.';
                        errEl.classList.remove('d-none');
                        btn.disabled = false;
                    }
                });
            });
        </script>
    @endif
</body>
</html>
