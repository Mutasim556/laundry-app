<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Delivery Person Login | FreshGo Laundry</title>
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/icons/material-design-iconic-font/css/materialdesignicons.min.css') }}">
    <style>
        body { background: linear-gradient(135deg, #FFF8E5 0%, #F8FAFC 100%); min-height: 100vh; font-family: 'Poppins', sans-serif; }
        .login-card { max-width: 420px; width: 100%; border-radius: 18px; border: 1px solid #FDE68A; box-shadow: 0 12px 32px rgba(23,23,23,0.08); }
        .btn-yellow { background-color: #FFC629; color: #171717; font-weight: 700; border: 1px solid #EAB308; }
        .btn-yellow:hover { background-color: #F5B800; color: #171717; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
<div class="card login-card bg-white p-4">
    <div class="text-center mb-3">
        <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width: 48px; height: 48px; background: #FFC629; color: #171717; font-size: 1.5rem;">
            <i class="mdi mdi-motorbike"></i>
        </div>
        <h5 class="fw-bold mb-1">Delivery Person Portal</h5>
        <div class="small text-muted">Sign in with your Email or Phone Number</div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('delivery-person.login.submit') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">Email or Phone Number</label>
            <input type="text" name="login" value="{{ old('login') }}" class="form-control" required autofocus placeholder="driver@example.com or +447700900111">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Password</label>
            <div class="input-group">
                <input type="password" id="dpPasswordInput" name="password" class="form-control" required>
                <button type="button" class="btn btn-outline-secondary" onclick="const i=document.getElementById('dpPasswordInput');i.type=i.type==='password'?'text':'password';">
                    <i class="mdi mdi-eye-outline"></i>
                </button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 small">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <a href="{{ route('delivery-person.password.request') }}" class="text-decoration-none">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-yellow w-100 py-2">Sign In</button>
    </form>
</div>
</body>
</html>
