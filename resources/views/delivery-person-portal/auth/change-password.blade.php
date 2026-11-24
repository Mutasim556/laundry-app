@extends('delivery-person-portal.layouts.app')
@section('title', 'Change Password')
@section('page_title', 'Change Password')

@section('content')
<div class="dp-card p-3 p-md-4" style="max-width: 480px;">
    <h6 class="fw-bold mb-3">Update Your Portal Password</h6>
    <form method="POST" action="{{ route('delivery-person.password.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">New Password</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn dp-btn-yellow px-4">Update Password</button>
    </form>
</div>
@endsection
