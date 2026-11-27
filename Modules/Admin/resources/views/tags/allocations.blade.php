@extends('admin::layouts.app')

@section('title', 'Assign Tags to Delivery Persons')

@include('admin::tags.partials.shared-styles')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
<style>
    .select2-container--default .select2-selection--single {
        height: 32px;
        padding: 2px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 30px;
    }
    .select2-dropdown {
        z-index: 1065 !important;
    }
</style>
@endpush

@section('content')
@php
    $dpWithStock = $deliveryPersons->filter(fn ($d) => ($d->allocated_unused_count + $d->active_assigned_count) > 0)->count();
@endphp
<div class="container-fluid py-3 tm-anim">
    <!-- Page Header -->
    <div class="tm-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">
                        Tag Management
                    </span>
                    <span class="badge rounded-pill bg-white text-dark border" style="font-size: 0.72rem;">
                        Delivery Person Stock
                    </span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #171717;">Assign Tags to Delivery Persons</h4>
                <div class="small text-muted">Allocate available physical tags to Delivery Person inventory with full audit traceability.</div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.tags.index') }}" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                    <i data-feather="layers" style="width: 14px; height: 14px;" class="me-1"></i>Tag Inventory (<span id="hdrAvailable">{{ $summary['available'] }}</span>)
                </a>
                <button type="button" class="btn btn-sm btn-warning px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#allocateTagsModal">
                    <i data-feather="user-plus" style="width: 14px; height: 14px;" class="me-1"></i>Allocate Tags to Delivery Person
                </button>
            </div>
        </div>
    </div>

    @include('admin::tags.partials.nav-pills')

    <!-- Summary KPI Cards -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="tm-kpi-card">
                <div class="tm-kpi-bar" style="background: #16A34A;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Available Tags</div>
                        <div class="fs-4 fw-bold text-success" id="kpiAvailable">{{ $summary['available'] }}</div>
                    </div>
                    <span class="tm-kpi-icon" style="background: #ECFDF5; color: #16A34A;">
                        <i data-feather="check-circle" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tm-kpi-card">
                <div class="tm-kpi-bar" style="background: #3B82F6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Allocated Tags</div>
                        <div class="fs-4 fw-bold text-primary" id="kpiAllocated">{{ $summary['allocated'] }}</div>
                    </div>
                    <span class="tm-kpi-icon" style="background: #EFF6FF; color: #2563EB;">
                        <i data-feather="user-check" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tm-kpi-card">
                <div class="tm-kpi-bar" style="background: #8B5CF6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Total Allocations</div>
                        <div class="fs-4 fw-bold" style="color: #6D28D9;">{{ $allocations->total() }}</div>
                    </div>
                    <span class="tm-kpi-icon" style="background: #F5F3FF; color: #7C3AED;">
                        <i data-feather="package" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tm-kpi-card">
                <div class="tm-kpi-bar" style="background: #F59E0B;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">DPs With Inventory</div>
                        <div class="fs-4 fw-bold" style="color: #D97706;">{{ $dpWithStock }}</div>
                    </div>
                    <span class="tm-kpi-icon" style="background: #FFFBEB; color: #D97706;">
                        <i data-feather="users" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div id="allocAlert"></div>

    <!-- Delivery Person Current Tag Balances -->
    <div class="tm-section-card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold small text-dark"><i data-feather="users" style="width: 15px; height: 15px;" class="me-1 text-primary"></i>Delivery Person Current Tag Balances</span>
        </div>
        <div class="card-body p-0">
            <div class="tm-table-scroll">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Delivery Person</th>
                            <th>Phone</th>
                            <th>Unused Allocated Tags</th>
                            <th>Active On Bookings</th>
                            <th class="text-end">Quick Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveryPersons as $dp)
                            <tr>
                                <td><code>{{ $dp->code }}</code></td>
                                <td class="fw-semibold">{{ $dp->full_name }}</td>
                                <td class="text-nowrap">{{ $dp->phone_display }}</td>
                                <td><span class="badge bg-primary">{{ $dp->allocated_unused_count }}</span></td>
                                <td><span class="badge bg-info text-dark">{{ $dp->active_assigned_count }}</span></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-quick-allocate" data-dp-id="{{ $dp->id }}">
                                        Allocate Tags
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="tm-empty-state">
                                        <div class="tm-empty-icon"><i data-feather="users" style="width:22px;height:22px;"></i></div>
                                        <div class="fw-semibold text-dark">No active Delivery Persons found.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Allocation Batch History -->
    <div class="tm-section-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold small text-dark"><i data-feather="clock" style="width: 15px; height: 15px;" class="me-1 text-success"></i>Allocation Batch History</span>
            <input type="text" id="allocSearch" class="form-control form-control-sm" style="max-width: 240px;" placeholder="Filter by ref or Delivery Person...">
        </div>
        <div class="card-body p-0" id="allocationsTableWrapper">
            @include('admin::tags.partials.allocations-table', compact('allocations'))
        </div>
    </div>
</div>

{{-- Bootstrap 5 Allocate Tags Modal with Select2 --}}
<div class="modal fade" id="allocateTagsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="allocateTagsForm" method="POST" action="{{ route('admin.tags.allocations.store') }}" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            @csrf
            <div class="modal-header bg-light border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">Allocate Laundry Tags to Delivery Person</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="modalAllocError"></div>
                <div class="alert alert-info small py-2 mb-3">
                    Available Admin Inventory: <strong id="modalAvailCount">{{ $summary['available'] }}</strong> tag(s) ready for allocation.
                </div>

                <div class="mb-3">
                    <label for="allocDpSelect" class="form-label fw-semibold small">Delivery Person <span class="text-danger">*</span></label>
                    <select name="delivery_person_id" id="allocDpSelect" class="form-select form-select-sm" style="width: 100%;" required>
                        <option value="">Select a Delivery Person...</option>
                        @foreach($deliveryPersons as $dp)
                            <option value="{{ $dp->id }}">{{ $dp->full_name }} ({{ $dp->code }} — {{ $dp->allocated_unused_count }} unused)</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="allocBatchSelect" class="form-label fw-semibold small">Source Tag Batch (Optional)</label>
                    <select name="batch_id" id="allocBatchSelect" class="form-select form-select-sm">
                        <option value="">Any Available Stock (Oldest Available First)</option>
                        @foreach($batchesWithAvailable as $b)
                            <option value="{{ $b->id }}">{{ $b->batch_reference }} — {{ $b->available_tags_count }} available</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="allocQuantity" class="form-label fw-semibold small">Quantity of Tags to Allocate <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" id="allocQuantity" min="1" max="500" value="10" class="form-control form-control-sm" required>
                </div>

                <div class="mb-0">
                    <label for="allocNotes" class="form-label fw-semibold small">Allocation Note (Optional)</label>
                    <input type="text" name="notes" id="allocNotes" maxlength="500" class="form-control form-control-sm" placeholder="e.g. Morning collection shift stock">
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="btnConfirmAllocate" class="btn btn-sm btn-warning fw-semibold">Confirm Allocation</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
(function () {
    const modalEl = document.getElementById('allocateTagsModal');
    const dpSelect = $('#allocDpSelect');
    const wrapper = document.getElementById('allocationsTableWrapper');

    if ($.fn.select2 && !dpSelect.hasClass('select2-hidden-accessible')) {
        dpSelect.select2({
            dropdownParent: $('#allocateTagsModal'),
            placeholder: 'Search Delivery Person by name or code...',
            width: '100%'
        });
    }

    modalEl?.addEventListener('hidden.bs.modal', () => {
        document.getElementById('modalAllocError').innerHTML = '';
    });

    document.querySelectorAll('.btn-quick-allocate').forEach(btn => {
        btn.addEventListener('click', () => {
            dpSelect.val(btn.dataset.dpId).trigger('change');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });

    async function refreshAllocations(page = 1) {
        wrapper.classList.add('tm-loading-overlay');
        const q = document.getElementById('allocSearch')?.value || '';
        const url = new URL(`{{ route('admin.tags.allocations.index') }}`, window.location.origin);
        url.searchParams.set('page', String(page));
        if (q) url.searchParams.set('search', q);
        try {
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (data.success) {
                wrapper.innerHTML = data.table_html;
                if (data.summary) {
                    document.getElementById('hdrAvailable').textContent = data.summary.available;
                    document.getElementById('kpiAvailable').textContent = data.summary.available;
                    document.getElementById('kpiAllocated').textContent = data.summary.allocated;
                    document.getElementById('modalAvailCount').textContent = data.summary.available;
                }
                if (window.feather) feather.replace();
            }
        } finally {
            wrapper.classList.remove('tm-loading-overlay');
        }
    }

    let searchTimer = null;
    document.getElementById('allocSearch')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => refreshAllocations(1), 300);
    });

    document.addEventListener('click', (e) => {
        const a = e.target.closest('.fg-alloc-pagination a');
        if (!a) return;
        e.preventDefault();
        const p = new URL(a.href, window.location.origin).searchParams.get('page') || 1;
        refreshAllocations(parseInt(p, 10));
    });

    document.getElementById('allocateTagsForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('btnConfirmAllocate');
        if (btn.disabled) return;
        const errBox = document.getElementById('modalAllocError');
        errBox.innerHTML = '';
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Allocating...';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                document.getElementById('allocAlert').innerHTML = `<div class="alert alert-success small py-2">${data.message}</div>`;
                if (window.tmToast) window.tmToast(data.message, 'success');
                await refreshAllocations(1);
            } else {
                errBox.innerHTML = `<div class="alert alert-danger small py-2">${data.message || 'Allocation failed.'}</div>`;
            }
        } catch (err) {
            errBox.innerHTML = `<div class="alert alert-danger small py-2">Network error during allocation.</div>`;
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirm Allocation';
        }
    });
})();
</script>
@endpush
