<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Delivery Person Portal</title>
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
</head>
<body class="d-flex align-items-center justify-content-center p-3" style="background: #FFF8E5; min-height: 100vh;">
<div class="card border-0 shadow p-4" style="max-width: 420px; width: 100%; border-radius: 16px;">
    <h5 class="fw-bold mb-2">Reset Portal Password</h5>
    <p class="small text-muted">Enter your Email or Phone Number to request a password reset.</p>
    @if(session('status'))
        <div class="alert alert-info py-2 small">{{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('delivery-person.password.email') }}">
        @csrf
        <div class="mb-3">
            <input type="text" name="login" class="form-control" required placeholder="Email or Phone Number">
        </div>
        <button type="submit" class="btn btn-warning fw-bold w-100 mb-2">Submit Request</button>
        <a href="{{ route('delivery-person.login') }}" class="btn btn-light w-100 btn-sm">Back to Login</a>
    </form>
</div>
</body>
</html>
