@extends('store-portal.layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="sp-card p-3 p-md-4" style="max-width: 520px;">
    <h5 class="fw-bold mb-3" style="color: #171717;">Change Store Account Password</h5>
    <form method="POST" action="{{ route('store.password.change.update') }}" class="small">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label fw-semibold">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">New Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button type="submit" class="btn sp-btn-yellow px-4">Update Password</button>
    </form>
</div>
@endsection
