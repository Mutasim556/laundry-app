@extends('admin::layouts.app')

@section('title', 'Delivery Persons - Admin Panel')

@section('content')
<div class="saas-page-shell py-4 px-3 px-md-4">
    {{-- Header --}}
    <div class="saas-header-card">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h1 class="saas-header-title">Delivery Persons</h1>
                <p class="saas-header-subtitle">Manage couriers, UK postcode coverage, assignments, activity, and ledger payouts</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.delivery-assignments.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold">
                    <i class="mdi mdi-navigation-variant-outline me-1"></i>Assignments
                </a>
                <a href="{{ route('admin.delivery-payouts.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold">
                    <i class="mdi mdi-wallet-outline me-1"></i>Balances &amp; Payouts
                </a>
                @can('create-delivery-persons', 'admin')
                    <a href="{{ route('admin.delivery-persons.create') }}" class="btn btn-saas-primary">
                        <i class="mdi mdi-plus font-18"></i>
                        <span>Add Delivery Person</span>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- 6 Dashboard Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="saas-stat-card">
                <div>
                    <div class="saas-stat-label">Total Couriers</div>
                    <div class="saas-stat-value">{{ $stats['total'] }}</div>
                </div>
                <span class="saas-stat-icon blue"><i class="mdi mdi-account-group-outline"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="saas-stat-card">
                <div>
                    <div class="saas-stat-label">Active</div>
                    <div class="saas-stat-value">{{ $stats['active'] }}</div>
                </div>
                <span class="saas-stat-icon green"><i class="mdi mdi-check-circle-outline"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="saas-stat-card">
                <div>
                    <div class="saas-stat-label">Inactive</div>
                    <div class="saas-stat-value">{{ $stats['inactive'] }}</div>
                </div>
                <span class="saas-stat-icon purple"><i class="mdi mdi-pause-circle-outline"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="saas-stat-card">
                <div>
                    <div class="saas-stat-label">Assigned Now</div>
                    <div class="saas-stat-value">{{ $stats['currently_assigned'] }}</div>
                </div>
                <span class="saas-stat-icon yellow"><i class="mdi mdi-truck-delivery-outline"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="saas-stat-card">
                <div>
                    <div class="saas-stat-label">Completed Legs</div>
                    <div class="saas-stat-value">{{ $stats['completed_assignments'] }}</div>
                </div>
                <span class="saas-stat-icon green"><i class="mdi mdi-package-variant-closed-check"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="saas-stat-card">
                <div>
                    <div class="saas-stat-label">Outstanding</div>
                    <div class="saas-stat-value" style="font-size: 1.25rem;">{{ $currencyService->formatMinor($stats['outstanding_balance_minor'], null, true) }}</div>
                </div>
                <span class="saas-stat-icon yellow"><i class="mdi mdi-cash-multiple"></i></span>
            </div>
        </div>
    </div>

    {{-- Filters & Table --}}
    <div class="saas-surface-card">
        <form method="GET" action="{{ route('admin.delivery-persons.index') }}" class="saas-filter-bar">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, ID, email, phone...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                    <select name="work_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('work_status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('work_status') === 'inactive')>Inactive</option>
                        <option value="suspended" @selected(request('work_status') === 'suspended')>Suspended</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Assignment</label>
                    <select name="assignment_state" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="assigned" @selected(request('assignment_state') === 'assigned')>Currently Assigned</option>
                        <option value="unassigned" @selected(request('assignment_state') === 'unassigned')>Unassigned</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Postcode</label>
                    <select name="postcode" class="form-select form-select-sm">
                        <option value="">All Postcodes</option>
                        @foreach($coveredPostcodes as $pc)
                            <option value="{{ $pc }}" @selected(request('postcode') === $pc)>{{ $pc }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Store</label>
                    <select name="store_id" class="form-select form-select-sm">
                        <option value="">All Stores</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" @selected((string) request('store_id') === (string) $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-saas-primary flex-grow-1">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table saas-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Courier</th>
                        <th>ID</th>
                        <th>Email &amp; Phone</th>
                        <th>Assigned Postcodes</th>
                        <th>Active Jobs</th>
                        <th>Completed Orders</th>
                        <th>Outstanding Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveryPersons as $person)
                        @php $bal = $balances[$person->id]; @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($person->profile_photo)
                                        <img src="{{ asset('storage/' . $person->profile_photo) }}" alt="{{ $person->full_name }}" class="rounded-circle border" style="width: 38px; height: 38px; object-fit: cover;">
                                    @else
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; background: #fff8e5; color: #b45309; border: 1px solid rgba(255,198,41,0.5);">
                                            {{ $person->initials }}
                                        </span>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.delivery-persons.show', $person) }}" class="fw-bold text-dark text-decoration-none">{{ $person->full_name }}</a>
                                        <div class="small text-muted">{{ $person->store?->name ?? 'Unassigned Store' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="saas-code-pill">{{ $person->code }}</span></td>
                            <td>
                                <div class="small">{{ $person->email }}</div>
                                <div class="small text-muted">{{ $person->phone_display }}</div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1" style="max-width: 220px;">
                                    @foreach($person->postcodes->take(3) as $pc)
                                        <span class="badge bg-light text-dark border">{{ $pc->postcode }}</span>
                                    @endforeach
                                    @if($person->postcodes->count() > 3)
                                        <span class="badge bg-warning-subtle text-dark">+{{ $person->postcodes->count() - 3 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td><span class="badge bg-info-subtle text-info rounded-pill">{{ $person->active_assignments_count }}</span></td>
                            <td><span class="badge bg-success-subtle text-success rounded-pill">{{ $person->completed_orders_count }}</span></td>
                            <td class="fw-bold text-nowrap">{{ $currencyService->formatMinor($bal['outstanding_balance_minor'], $bal['currency'], true) }}</td>
                            <td>
                                <span class="saas-status-badge {{ $person->work_status }}">{{ ucfirst($person->work_status) }}</span>
                                <div class="small text-muted mt-1">{{ ucfirst(str_replace('_', ' ', $person->availability_status)) }}</div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.delivery-persons.show', $person) }}" class="saas-action-btn view" title="View Profile"><i class="mdi mdi-eye-outline font-16"></i></a>
                                    @can('edit-delivery-persons', 'admin')
                                        <a href="{{ route('admin.delivery-persons.edit', $person) }}" class="saas-action-btn edit" title="Edit"><i class="mdi mdi-pencil-outline font-16"></i></a>
                                        <form method="POST" action="{{ route('admin.delivery-persons.toggle-status', $person) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="saas-action-btn toggle" title="{{ $person->work_status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                <i class="mdi {{ $person->work_status === 'active' ? 'mdi-pause' : 'mdi-play' }} font-16"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    <a href="{{ route('admin.delivery-assignments.index', ['delivery_person_id' => $person->id]) }}" class="saas-action-btn" title="Assignments"><i class="mdi mdi-navigation-variant-outline font-16"></i></a>
                                    <a href="{{ route('admin.delivery-activity.index', ['delivery_person_id' => $person->id]) }}" class="saas-action-btn" title="Activity"><i class="mdi mdi-history font-16"></i></a>
                                    <a href="{{ route('admin.delivery-payouts.index', ['delivery_person_id' => $person->id]) }}" class="saas-action-btn" title="Account Balance"><i class="mdi mdi-wallet-outline font-16"></i></a>
                                    @can('delete-delivery-persons', 'admin')
                                        <form method="POST" action="{{ route('admin.delivery-persons.destroy', $person) }}" class="d-inline" onsubmit="return confirm('Archive or delete {{ addslashes($person->full_name) }}? Historical assignments and ledger records are preserved.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="saas-action-btn delete" title="Archive / Delete"><i class="mdi mdi-archive-outline font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="mdi mdi-truck-outline d-block font-24 mb-2"></i>
                                No delivery persons found. Click <strong>Add Delivery Person</strong> to register your first courier.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deliveryPersons->hasPages())
            <div class="p-3 border-top">
                {{ $deliveryPersons->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
