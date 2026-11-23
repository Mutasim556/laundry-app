@extends('delivery-person-portal.layouts.app')
@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('content')
<div class="dp-card p-3 p-md-4" style="max-width: 600px;">
    <h6 class="fw-bold mb-3">{{ $person->full_name }} ({{ $person->code }})</h6>
    <div class="small mb-3">
        <div><strong>Email:</strong> {{ $person->email }}</div>
        <div><strong>Phone:</strong> {{ $person->phone_display }}</div>
        <div><strong>Assigned Store:</strong> {{ $person->store?->name ?? 'All Stores' }}</div>
        <div><strong>Coverage Postcodes:</strong> {{ $person->postcodes->pluck('postcode')->implode(', ') ?: 'None' }}</div>
    </div>
    <form method="POST" action="{{ route('delivery-person.profile.update') }}" class="d-flex align-items-end gap-2">
        @csrf
        <div class="flex-grow-1">
            <label class="form-label small fw-semibold mb-1">Availability Status</label>
            <select name="availability_status" class="form-select form-select-sm">
                <option value="available" {{ $person->availability_status === 'available' ? 'selected' : '' }}>Available</option>
                <option value="busy" {{ $person->availability_status === 'busy' ? 'selected' : '' }}>Busy</option>
                <option value="off_duty" {{ $person->availability_status === 'off_duty' ? 'selected' : '' }}>Off Duty</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm dp-btn-yellow px-3">Save</button>
    </form>
</div>
@endsection
