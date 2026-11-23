<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Store Portal Login | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/icons/material-design-iconic-font/css/materialdesignicons.min.css') }}">
    <style>
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #F8FAFC;
            color: #171717;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .sp-login-shell {
            max-width: 920px;
            width: 100%;
            background: #FFFFFF;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            border: 1px solid #E2E8F0;
        }
        .sp-login-brand {
            background: linear-gradient(145deg, #FFF8E5 0%, #FFC629 100%);
            color: #171717;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .sp-login-input {
            height: 44px;
            border-radius: 10px;
            border-color: #CBD5E1;
            color: #171717;
            font-size: 0.9rem;
        }
        .sp-login-input:focus {
            border-color: #FFC629;
            box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.25);
        }
        .sp-btn-primary {
            background-color: #FFC629;
            border-color: #FFC629;
            color: #171717;
            font-weight: 700;
            height: 44px;
            border-radius: 10px;
        }
        .sp-btn-primary:hover {
            background-color: #f0b516;
            border-color: #f0b516;
            color: #171717;
        }
    </style>
</head>
<body>
<div class="sp-login-shell">
    <div class="row g-0">
        <div class="col-12 col-lg-5 d-none d-lg-block">
            <div class="sp-login-brand">
                <div>
                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3 bg-white shadow-sm" style="width: 48px; height: 48px;">
                        <i class="mdi mdi-store text-warning" style="font-size: 1.6rem;"></i>
                    </div>
                    <h2 class="fw-bold mb-2" style="color: #171717;">FreshGo Store Portal</h2>
                    <p class="small mb-0" style="color: #334155;">
                        Manage assigned laundry bookings, track fulfillment schedules, and review finalized store earnings in one place.
                    </p>
                </div>
                <div class="p-3 rounded-3 bg-white bg-opacity-75 small" style="color: #171717;">
                    <div class="fw-bold mb-1"><i class="mdi mdi-check-circle text-success me-1"></i>Partner Access</div>
                    <div>Sign in with your authorized store email or international phone number.</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7 p-4 p-md-5">
            <div class="d-lg-none mb-3">
                <span class="badge rounded-pill px-3 py-1 fw-bold" style="background: #FFF8E5; color: #171717; border: 1px solid #FFC629;">
                    <i class="mdi mdi-store me-1"></i>FreshGo Store Portal
                </span>
            </div>

            <h3 class="fw-bold mb-1" style="color: #171717;">Store Sign In</h3>
            <p class="small mb-4" style="color: #475569;">Enter your store email address or phone number and password.</p>

            @if(session('status'))
                <div class="alert alert-success small py-2 px-3 rounded-3">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger small py-2 px-3 rounded-3">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('store.login.submit') }}" id="storeLoginForm">
                @csrf
                <div class="mb-3">
                    <label for="storeLoginInput" class="form-label small fw-semibold" style="color: #171717;">Email or Phone Number</label>
                    <input id="storeLoginInput"
                           type="text"
                           name="login"
                           value="{{ old('login') }}"
                           class="form-control sp-login-input"
                           placeholder="store@example.com or +447700900111"
                           required
                           autofocus>
                </div>

                <div class="mb-3">
                    <label for="storePasswordInput" class="form-label small fw-semibold" style="color: #171717;">Password</label>
                    <div class="input-group">
                        <input id="storePasswordInput"
                               type="password"
                               name="password"
                               class="form-control sp-login-input"
                               placeholder="Enter your password"
                               required>
                        <button type="button" class="btn btn-outline-secondary" id="btnTogglePassword" style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;" aria-label="Show password">
                            <i class="mdi mdi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 small">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberStore" value="1">
                        <label class="form-check-label" for="rememberStore" style="color: #475569;">Remember me</label>
                    </div>
                    <a href="{{ route('store.password.request') }}" class="text-decoration-none fw-semibold" style="color: #1D4ED8;">Forgot password?</a>
                </div>

                <button type="submit" class="btn sp-btn-primary w-100" id="btnStoreSignIn">
                    Sign In
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('btnTogglePassword')?.addEventListener('click', function () {
        const input = document.getElementById('storePasswordInput');
        const icon = document.getElementById('togglePasswordIcon');
        if (!input) return;
        const isPwd = input.type === 'password';
        input.type = isPwd ? 'text' : 'password';
        if (icon) {
            icon.className = isPwd ? 'mdi mdi-eye-off' : 'mdi mdi-eye';
        }
    });
    document.getElementById('storeLoginForm')?.addEventListener('submit', function () {
        const btn = document.getElementById('btnStoreSignIn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Signing in...';
        }
    });
</script>
</body>
</html>
