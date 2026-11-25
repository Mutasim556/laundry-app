<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify Your Email | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
    <style>
        .otp-digit-box {
            width: 46px;
            height: 54px;
            text-align: center;
            font-size: 1.35rem;
            font-weight: 800;
            border-radius: 12px;
            border: 1.8px solid #E5E7EB;
            background: #FAFAF7;
            color: #171717;
        }
        .otp-digit-box:focus {
            border-color: #FFC629;
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.25);
            outline: none;
        }
        @media (max-width: 400px) {
            .otp-digit-box {
                width: 40px;
                height: 48px;
                font-size: 1.15rem;
            }
        }
    </style>
</head>
<body class="booking-page-body">
    
    @include('shared.header')

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="mb-3">
                        <a href="{{ route('login') }}" class="booking-back-link">&lsaquo; Change Email</a>
                    </div>

                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold text-dark mb-1">Verify Your Email</h1>
                        <p class="text-secondary small mb-1">
                            We've sent a 6-digit verification code to your email address.
                        </p>
                        <div class="small fw-bold text-dark">{{ $email }}</div>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success small rounded-3">{{ session('status') }}</div>
                    @endif

                    @error('otp')
                        <div class="alert alert-danger small rounded-3" role="alert">{{ $message }}</div>
                    @enderror

                    <form method="POST" action="{{ route('customer.otp.verify') }}" id="otpVerifyForm" novalidate>
                        @csrf
                        <input type="hidden" name="otp" id="otpCombinedInput" value="">

                        <div class="d-flex justify-content-center gap-2 mb-4" id="otpBoxesContainer">
                            @for ($i = 0; $i < 6; $i++)
                                <input type="text"
                                       class="otp-digit-box js-otp-digit"
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       maxlength="1"
                                       autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                       aria-label="Digit {{ $i + 1 }}"
                                       {{ $i === 0 ? 'autofocus' : '' }}>
                            @endfor
                        </div>

                        <button type="submit" class="btn btn-warning w-100 rounded-pill py-3 fw-bold text-dark shadow-sm mb-3">
                            Verify &amp; Continue
                        </button>
                    </form>

                    <div class="text-center">
                        <form method="POST" action="{{ route('customer.otp.resend') }}" class="d-inline">
                            @csrf
                            <button type="submit"
                                    id="btnResendOtp"
                                    class="btn btn-link text-dark fw-semibold small text-decoration-none"
                                    {{ $cooldownSeconds > 0 ? 'disabled' : '' }}>
                                @if ($cooldownSeconds > 0)
                                    Resend Code (<span id="resendCountdown">{{ $cooldownSeconds }}</span>s)
                                @else
                                    Resend Code
                                @endif
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('shared.footer')
    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const digits = Array.from(document.querySelectorAll('.js-otp-digit'));
            const combined = document.getElementById('otpCombinedInput');
            const form = document.getElementById('otpVerifyForm');

            const syncCombined = () => {
                combined.value = digits.map(d => d.value.replace(/\D/g, '')).join('');
            };

            digits.forEach((input, idx) => {
                input.addEventListener('input', (e) => {
                    const clean = input.value.replace(/\D/g, '');
                    if (clean.length > 1) {
                        clean.split('').slice(0, 6).forEach((ch, i) => {
                            if (digits[i]) digits[i].value = ch;
                        });
                        syncCombined();
                        digits[Math.min(clean.length, 5)].focus();
                        return;
                    }
                    input.value = clean;
                    syncCombined();
                    if (clean && idx < digits.length - 1) {
                        digits[idx + 1].focus();
                    }
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && idx > 0) {
                        digits[idx - 1].focus();
                    } else if (e.key === 'ArrowLeft' && idx > 0) {
                        digits[idx - 1].focus();
                    } else if (e.key === 'ArrowRight' && idx < digits.length - 1) {
                        digits[idx + 1].focus();
                    }
                });

                input.addEventListener('paste', (e) => {
                    const text = (e.clipboardData || window.clipboardData).getData('text') || '';
                    const pasted = text.replace(/\D/g, '').slice(0, 6);
                    if (!pasted) return;
                    e.preventDefault();
                    pasted.split('').forEach((ch, i) => {
                        if (digits[i]) digits[i].value = ch;
                    });
                    syncCombined();
                    digits[Math.min(pasted.length - 1, 5)].focus();
                });
            });

            form?.addEventListener('submit', syncCombined);

            let remaining = {{ (int) $cooldownSeconds }};
            const btnResend = document.getElementById('btnResendOtp');
            const countdownSpan = document.getElementById('resendCountdown');
            if (remaining > 0 && btnResend && countdownSpan) {
                const timer = setInterval(() => {
                    remaining -= 1;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        btnResend.disabled = false;
                        btnResend.textContent = 'Resend Code';
                    } else {
                        countdownSpan.textContent = String(remaining);
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>
