@extends('admin::layouts.app')

@section('title', $deliveryPerson->full_name . ' - Courier Profile')

@section('content')
<div class="saas-page-shell py-4 px-3 px-md-4">
    {{-- Top Profile Header --}}
    <div class="saas-header-card">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                @if($deliveryPerson->profile_photo)
                    <img src="{{ asset('storage/' . $deliveryPerson->profile_photo) }}" alt="{{ $deliveryPerson->full_name }}" class="rounded-circle border" style="width: 64px; height: 64px; object-fit: cover;">
                @else
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold fs-4" style="width: 64px; height: 64px; background: #fff8e5; color: #b45309; border: 2px solid #ffc629;">
                        {{ $deliveryPerson->initials }}
                    </span>
                @endif
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h1 class="saas-header-title mb-0">{{ $deliveryPerson->full_name }}</h1>
                        <span class="saas-code-pill">{{ $deliveryPerson->code }}</span>
                        <span class="saas-status-badge {{ $deliveryPerson->work_status }}">{{ ucfirst($deliveryPerson->work_status) }}</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">{{ ucfirst(str_replace('_', ' ', $deliveryPerson->availability_status)) }}</span>
                    </div>
                    <div class="small text-muted mt-1">
                        <i class="mdi mdi-phone-outline me-1"></i>{{ $deliveryPerson->phone_display }}
                        &middot; <i class="mdi mdi-email-outline me-1"></i>{{ $deliveryPerson->email }}
                        &middot; <i class="mdi mdi-store-outline me-1"></i>{{ $deliveryPerson->store?->name ?? 'Unassigned Store' }}
                        &middot; <i class="mdi mdi-map-marker-multiple-outline me-1"></i>{{ $deliveryPerson->postcodes->count() }} Covered Postcodes
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.delivery-persons.index') }}" class="btn btn-outline-secondary rounded-3">Back</a>
                @can('edit-delivery-persons', 'admin')
                    <a href="{{ route('admin.delivery-persons.edit', $deliveryPerson) }}" class="btn btn-saas-primary">
                        <i class="mdi mdi-pencil-outline"></i> Edit Profile
                    </a>
                @endcan
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Top 7 Statistics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <div class="saas-stat-card"><div><div class="saas-stat-label">Assigned Jobs</div><div class="saas-stat-value">{{ $stats['total_assigned'] }}</div></div><span class="saas-stat-icon blue"><i class="mdi mdi-clipboard-list-outline"></i></span></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="saas-stat-card"><div><div class="saas-stat-label">Completed</div><div class="saas-stat-value">{{ $stats['completed_assignments'] }}</div><div class="saas-stat-hint">{{ $stats['unique_orders_handled'] }} unique orders</div></div><span class="saas-stat-icon green"><i class="mdi mdi-check-circle-outline"></i></span></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="saas-stat-card"><div><div class="saas-stat-label">Active</div><div class="saas-stat-value">{{ $stats['active_assignments'] }}</div></div><span class="saas-stat-icon yellow"><i class="mdi mdi-truck-fast-outline"></i></span></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="saas-stat-card"><div><div class="saas-stat-label">Failed / Canc.</div><div class="saas-stat-value">{{ $stats['failed_assignments'] + $stats['cancelled_assignments'] }}</div></div><span class="saas-stat-icon purple"><i class="mdi mdi-alert-circle-outline"></i></span></div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="saas-stat-card"><div><div class="saas-stat-label">Total Earned</div><div class="saas-stat-value" style="font-size: 1.2rem;">{{ $currencyService->formatMinor($balance['total_payable_minor'], $balance['currency'], true) }}</div></div></div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="saas-stat-card"><div><div class="saas-stat-label">Total Paid</div><div class="saas-stat-value" style="font-size: 1.2rem;">{{ $currencyService->formatMinor($balance['total_paid_minor'], $balance['currency'], true) }}</div></div></div>
        </div>
        <div class="col-12 col-md-4 col-xl">
            <div class="saas-stat-card"><div><div class="saas-stat-label">Outstanding</div><div class="saas-stat-value text-warning-emphasis" style="font-size: 1.2rem;">{{ $currencyService->formatMinor($balance['outstanding_balance_minor'], $balance['currency'], true) }}</div></div></div>
        </div>
    </div>

    {{-- 8 Tabs --}}
    <div class="saas-surface-card">
        <div class="p-3 border-bottom bg-light-subtle">
            <ul class="nav nav-pills flex-nowrap overflow-auto gap-1" id="dpProfileTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active text-nowrap" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button">Overview</button></li>
                <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#tab-postcodes" type="button">Assigned Postcodes ({{ $deliveryPerson->postcodes->count() }})</button></li>
                <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#tab-active" type="button">Active Assignments ({{ $activeAssignments->count() }})</button></li>
                <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#tab-completed" type="button">Completed Deliveries ({{ $completedAssignments->count() }})</button></li>
                <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#tab-activity" type="button">Activity History</button></li>
                <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#tab-balance" type="button">Account Balance</button></li>
                @can('view-delivery-documents', 'admin')
                    <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#tab-documents" type="button">Documents (Restricted)</button></li>
                @endcan
                <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="pill" data-bs-target="#tab-payments" type="button">Payment History</button></li>
            </ul>
        </div>

        <div class="tab-content p-4">
            {{-- 1. Overview --}}
            <div class="tab-pane fade show active" id="tab-overview">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="saas-form-section h-100 mb-0">
                            <div class="saas-section-heading"><span class="saas-section-badge"><i class="mdi mdi-account-outline"></i></span><span>Personal &amp; Residential Details</span></div>
                            <p class="small mb-2"><strong>Date of Birth:</strong> {{ $deliveryPerson->date_of_birth?->format('d M Y') }}</p>
                            <p class="small mb-2"><strong>Residential Address:</strong> {{ $deliveryPerson->full_residential_address }}</p>
                            <p class="small mb-2"><strong>Emergency Contact:</strong> {{ $deliveryPerson->emergency_contact_name ?: '—' }} {{ $deliveryPerson->emergency_contact_phone ? '(' . $deliveryPerson->emergency_contact_phone . ')' : '' }}</p>
                            <p class="small mb-0"><strong>Internal Notes:</strong> {{ $deliveryPerson->internal_notes ?: '—' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="saas-form-section h-100 mb-0">
                            <div class="saas-section-heading"><span class="saas-section-badge"><i class="mdi mdi-briefcase-outline"></i></span><span>Work &amp; Rate Configuration</span></div>
                            <p class="small mb-2"><strong>Contract Type:</strong> {{ ucfirst($deliveryPerson->employment_type) }} (Joined: {{ $deliveryPerson->joining_date?->format('d M Y') ?? '—' }})</p>
                            <p class="small mb-2"><strong>Vehicle:</strong> {{ $deliveryPerson->vehicle_type ?: '—' }} {{ $deliveryPerson->vehicle_registration ? '(' . $deliveryPerson->vehicle_registration . ')' : '' }}</p>
                            <p class="small mb-2"><strong>Per-Collection Rate:</strong> {{ $currencyService->formatMinor($deliveryPerson->per_collection_rate_minor, $deliveryPerson->currency, true) }}</p>
                            <p class="small mb-2"><strong>Per-Delivery Rate:</strong> {{ $currencyService->formatMinor($deliveryPerson->per_delivery_rate_minor, $deliveryPerson->currency, true) }}</p>
                            <p class="small mb-0"><strong>Avg Completion Time:</strong> {{ $stats['avg_completion_minutes'] !== null ? $stats['avg_completion_minutes'] . ' mins' : '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Assigned Postcodes --}}
            <div class="tab-pane fade" id="tab-postcodes">
                <div class="d-flex flex-wrap gap-2">
                    @forelse($deliveryPerson->postcodes as $pc)
                        <span class="badge bg-light text-dark border fs-6 px-3 py-2">{{ $pc->postcode }}</span>
                    @empty
                        <p class="text-muted mb-0">No delivery coverage postcodes assigned.</p>
                    @endforelse
                </div>
            </div>

            {{-- 3. Active Assignments --}}
            <div class="tab-pane fade" id="tab-active">
                @include('admin::delivery-persons.partials.assignment-table', ['items' => $activeAssignments, 'currencyService' => $currencyService])
            </div>

            {{-- 4. Completed Deliveries --}}
            <div class="tab-pane fade" id="tab-completed">
                @include('admin::delivery-persons.partials.assignment-table', ['items' => $completedAssignments, 'currencyService' => $currencyService])
            </div>

            {{-- 5. Activity History Timeline --}}
            <div class="tab-pane fade" id="tab-activity">
                <div class="list-group list-group-flush">
                    @forelse($deliveryPerson->events as $event)
                        <div class="list-group-item px-0 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="saas-code-pill me-2">{{ $event->booking?->reference ?? 'Booking #' . $event->booking_id }}</span>
                                    <strong>{{ $event->title }}</strong>
                                    @if($event->description)
                                        <div class="small text-muted mt-1">{{ $event->description }}</div>
                                    @endif
                                </div>
                                <small class="text-muted text-nowrap">{{ $event->occurred_at?->format('d M Y, H:i') }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No recorded activity events yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- 6. Account Balance --}}
            <div class="tab-pane fade" id="tab-balance">
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><div class="p-3 border rounded-3 bg-light"><strong>Completed Earnings:</strong><div class="fs-5 fw-bold">{{ $currencyService->formatMinor($balance['earnings_minor'], $balance['currency'], true) }}</div></div></div>
                    <div class="col-md-3"><div class="p-3 border rounded-3 bg-light"><strong>Adjustments:</strong><div class="fs-5 fw-bold">{{ $currencyService->formatMinor($balance['net_adjustments_minor'], $balance['currency'], true) }}</div></div></div>
                    <div class="col-md-3"><div class="p-3 border rounded-3 bg-light"><strong>Total Paid Out:</strong><div class="fs-5 fw-bold">{{ $currencyService->formatMinor($balance['total_paid_minor'], $balance['currency'], true) }}</div></div></div>
                    <div class="col-md-3"><div class="p-3 border rounded-3" style="background: #fff8e5;"><strong>Outstanding Balance:</strong><div class="fs-5 fw-bold">{{ $currencyService->formatMinor($balance['outstanding_balance_minor'], $balance['currency'], true) }}</div></div></div>
                </div>
                @can('manage-delivery-payouts', 'admin')
                    <form method="POST" action="{{ route('admin.delivery-payouts.store') }}" class="row g-2 align-items-end border rounded-3 p-3 bg-light-subtle">
                        @csrf
                        <input type="hidden" name="delivery_person_id" value="{{ $deliveryPerson->id }}">
                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <div class="col-md-2"><label class="form-label small fw-semibold">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Method</label><select name="payment_method" class="form-select form-select-sm"><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="other">Other</option></select></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Date</label><input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Reference</label><input type="text" name="external_reference" class="form-control form-control-sm" placeholder="Bank / Tx Ref"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Notes</label><input type="text" name="notes" class="form-control form-control-sm"></div>
                        <div class="col-md-2 d-grid"><button type="submit" class="btn btn-sm btn-saas-primary">Record Payout</button></div>
                    </form>
                @endcan
            </div>

            {{-- 7. Documents (Restricted) --}}
            @can('view-delivery-documents', 'admin')
                <div class="tab-pane fade" id="tab-documents">
                    <div class="table-responsive">
                        <table class="table saas-table align-middle mb-0">
                            <thead><tr><th>Type</th><th>Masked Number</th><th>Expiry</th><th>Status</th><th>Files (Private)</th><th>Verify</th></tr></thead>
                            <tbody>
                                @forelse($deliveryPerson->documents as $doc)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $doc->document_type)) }}</td>
                                        <td><span class="saas-code-pill">{{ $doc->document_number_masked }}</span></td>
                                        <td>{{ $doc->expiry_date?->format('d M Y') ?? '—' }}</td>
                                        <td><span class="saas-status-badge {{ $doc->verification_status === 'verified' ? 'active' : 'pending' }}">{{ ucfirst($doc->verification_status) }}</span></td>
                                        <td>
                                            <a href="{{ route('admin.delivery-persons.documents.download', [$deliveryPerson, $doc, 'front']) }}" class="btn btn-sm btn-outline-primary" target="_blank">Front</a>
                                            @if($doc->back_image_path)
                                                <a href="{{ route('admin.delivery-persons.documents.download', [$deliveryPerson, $doc, 'back']) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Back</a>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.delivery-persons.documents.verify', [$deliveryPerson, $doc]) }}" class="d-flex gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <select name="verification_status" class="form-select form-select-sm" style="width: 120px;">
                                                    @foreach(['pending', 'verified', 'rejected', 'expired'] as $vs)
                                                        <option value="{{ $vs }}" @selected($doc->verification_status === $vs)>{{ ucfirst($vs) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-dark">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No identity documents uploaded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endcan

            {{-- 8. Payment History --}}
            <div class="tab-pane fade" id="tab-payments">
                <div class="table-responsive">
                    <table class="table saas-table align-middle mb-0">
                        <thead><tr><th>Reference</th><th>Date</th><th>Method</th><th>Amount</th><th>External Ref</th><th>Recorded By</th></tr></thead>
                        <tbody>
                            @forelse($deliveryPerson->payouts as $payout)
                                <tr>
                                    <td><span class="saas-code-pill">{{ $payout->payout_reference }}</span></td>
                                    <td>{{ $payout->payment_date?->format('d M Y') }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $payout->payment_method)) }}</td>
                                    <td class="fw-bold">{{ $currencyService->formatMinor($payout->amount_minor, $payout->currency, true) }}</td>
                                    <td>{{ $payout->external_reference ?: '—' }}</td>
                                    <td>{{ $payout->recordedByAdmin?->name ?? 'Admin' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No payouts recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
