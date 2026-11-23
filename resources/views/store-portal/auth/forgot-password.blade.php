<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | FreshGo Store Portal</title>
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
<div class="card border-0 shadow-sm rounded-4 p-4" style="max-width: 440px; width: 100%;">
    <h5 class="fw-bold mb-1">Reset Store Password</h5>
    <p class="small text-muted mb-3">Enter your store user email to receive a password reset link.</p>
    @if(session('status'))
        <div class="alert alert-success small py-2">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('store.password.email') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">Email Address</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <button type="submit" class="btn btn-warning fw-bold w-100">Send Reset Link</button>
        <div class="text-center mt-3 small">
            <a href="{{ route('store.login') }}" class="text-decoration-none">Back to Store Login</a>
        </div>
    </form>
</div>
</body>
</html>
