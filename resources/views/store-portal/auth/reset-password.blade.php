<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | FreshGo Store Portal</title>
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
<div class="card border-0 shadow-sm rounded-4 p-4" style="max-width: 440px; width: 100%;">
    <h5 class="fw-bold mb-3">Set New Store Password</h5>
    @if($errors->any())
        <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('store.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Email Address</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">New Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-warning fw-bold w-100">Reset Password</button>
    </form>
</div>
</body>
</html>
