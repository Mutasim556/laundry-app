@extends('store-portal.layouts.app')

@section('title', 'Store Profile')

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="sp-card p-3 p-md-4 h-100">
            <h5 class="fw-bold mb-3" style="color: #171717;">Store Information (Read-Only)</h5>
            <div class="row g-2 small">
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Store Name</div>
                    <div class="fw-bold">{{ $store->name }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Store Status</div>
                    <div class="fw-bold text-capitalize">{{ $store->status }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Public Phone</div>
                    <div class="fw-semibold">{{ trim(($store->phone_country_code ? $store->phone_country_code . ' ' : '') . $store->phone) ?: 'Not provided' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Store Email</div>
                    <div class="fw-semibold">{{ $store->email ?: 'Not provided' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted">Address</div>
                    <div class="fw-semibold">{{ $store->full_address ?: implode(', ', array_filter([$store->address_line_1, $store->city, $store->country_name])) }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Postcode</div>
                    <div class="fw-semibold">{{ $store->postcode ?: '-' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="text-muted">Platform Commission Rate</div>
                    <div class="fw-bold">{{ $store->commission_rate !== null ? number_format((float) $store->commission_rate, 2) . '%' : 'Not configured' }}</div>
                </div>
                @if($store->vendor)
                    <div class="col-12">
                        <div class="text-muted">Associated Vendor</div>
                        <div class="fw-semibold">{{ $store->vendor->name }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="sp-card p-3 p-md-4 h-100">
            <h5 class="fw-bold mb-3" style="color: #171717;">My Store User Account</h5>
            <form method="POST" action="{{ route('store.profile.update') }}" class="small">
                @csrf
                @method('PUT')
                <div class="mb-2">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $storeUser->name) }}" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Login Email (Managed by Admin)</label>
                    <input type="email" value="{{ $storeUser->email }}" class="form-control bg-light" readonly disabled>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label fw-semibold">Code</label>
                        <input type="text" name="phone_country_code" value="{{ old('phone_country_code', $storeUser->phone_country_code) }}" class="form-control" placeholder="+44">
                    </div>
                    <div class="col-8">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $storeUser->phone) }}" class="form-control" placeholder="7700900111">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('store.password.change') }}" class="small text-decoration-none fw-semibold">Change Password &rarr;</a>
                    <button type="submit" class="btn btn-sm sp-btn-yellow px-3">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
