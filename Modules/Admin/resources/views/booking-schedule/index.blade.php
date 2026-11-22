@extends('admin::layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <style>
        .sched-hero {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 16px;
            padding: 22px 26px;
            color: #fff;
        }
        .nav-pills .nav-link.active {
            background-color: #4f46e5;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="sched-hero mb-4">
        <h2 class="mb-1 fw-bold text-white">Booking Schedule Management</h2>
        <p class="mb-0 text-white-50 small">Manage collection &amp; delivery time slots, driver instructions, working days, and blocked dates.</p>
    </div>

    <ul class="nav nav-pills mb-4 gap-2" id="scheduleTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="tab-collection-slots" data-bs-toggle="pill" data-bs-target="#pane-collection-slots" type="button" role="tab">Collection Time Slots</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-delivery-slots" data-bs-toggle="pill" data-bs-target="#pane-delivery-slots" type="button" role="tab">Delivery Time Slots</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-instructions" data-bs-toggle="pill" data-bs-target="#pane-instructions" type="button" role="tab">Driver Instructions</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-availability" data-bs-toggle="pill" data-bs-target="#pane-availability" type="button" role="tab">Availability &amp; Blocked Dates</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB 1: COLLECTION TIME SLOTS -->
        <div class="tab-pane fade show active" id="pane-collection-slots" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Collection Time Slots</h5>
                    @can('manage-booking-schedule', 'admin')
                        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 js-open-slot-modal" data-type="collection">
                            <i class="mdi mdi-plus me-1"></i> Add Collection Slot
                        </button>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Label</th>
                                    <th>Time Window</th>
                                    <th>Weekdays</th>
                                    <th>Sort</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($collectionSlots as $slot)
                                    <tr>
                                        <td class="fw-semibold">{{ $slot->label }} @if($slot->name)<div class="small text-muted">{{ $slot->name }}</div>@endif</td>
                                        <td class="font-monospace small">{{ $slot->start_time }} - {{ $slot->end_time }}</td>
                                        <td>
                                            @foreach ($slot->weekdays->pluck('weekday')->sort() as $wd)
                                                <span class="badge bg-light text-dark border">{{ $weekdayNames[$wd] ?? $wd }}</span>
                                            @endforeach
                                        </td>
                                        <td>{{ $slot->sort_order }}</td>
                                        <td>
                                            <span class="badge {{ $slot->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $slot->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @can('manage-booking-schedule', 'admin')
                                                <button type="button" class="btn btn-sm btn-light js-edit-slot"
                                                    data-slot="{{ json_encode(['id' => $slot->id, 'type' => $slot->type, 'name' => $slot->name, 'label' => $slot->label, 'start_time' => $slot->start_time, 'end_time' => $slot->end_time, 'sort_order' => $slot->sort_order, 'is_active' => $slot->is_active, 'weekdays' => $slot->weekdays->pluck('weekday')->all()]) }}">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning js-toggle-slot" data-id="{{ $slot->id }}"><i class="mdi mdi-power"></i></button>
                                                <button type="button" class="btn btn-sm btn-danger js-delete-slot" data-id="{{ $slot->id }}"><i class="mdi mdi-delete"></i></button>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: DELIVERY TIME SLOTS -->
        <div class="tab-pane fade" id="pane-delivery-slots" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Delivery Time Slots</h5>
                    @can('manage-booking-schedule', 'admin')
                        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 js-open-slot-modal" data-type="delivery">
                            <i class="mdi mdi-plus me-1"></i> Add Delivery Slot
                        </button>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Label</th>
                                    <th>Time Window</th>
                                    <th>Weekdays</th>
                                    <th>Sort</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deliverySlots as $slot)
                                    <tr>
                                        <td class="fw-semibold">{{ $slot->label }} @if($slot->name)<div class="small text-muted">{{ $slot->name }}</div>@endif</td>
                                        <td class="font-monospace small">{{ $slot->start_time }} - {{ $slot->end_time }}</td>
                                        <td>
                                            @foreach ($slot->weekdays->pluck('weekday')->sort() as $wd)
                                                <span class="badge bg-light text-dark border">{{ $weekdayNames[$wd] ?? $wd }}</span>
                                            @endforeach
                                        </td>
                                        <td>{{ $slot->sort_order }}</td>
                                        <td>
                                            <span class="badge {{ $slot->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $slot->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @can('manage-booking-schedule', 'admin')
                                                <button type="button" class="btn btn-sm btn-light js-edit-slot"
                                                    data-slot="{{ json_encode(['id' => $slot->id, 'type' => $slot->type, 'name' => $slot->name, 'label' => $slot->label, 'start_time' => $slot->start_time, 'end_time' => $slot->end_time, 'sort_order' => $slot->sort_order, 'is_active' => $slot->is_active, 'weekdays' => $slot->weekdays->pluck('weekday')->all()]) }}">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning js-toggle-slot" data-id="{{ $slot->id }}"><i class="mdi mdi-power"></i></button>
                                                <button type="button" class="btn btn-sm btn-danger js-delete-slot" data-id="{{ $slot->id }}"><i class="mdi mdi-delete"></i></button>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: DRIVER INSTRUCTIONS -->
        <div class="tab-pane fade" id="pane-instructions" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Driver Instructions</h5>
                    @can('manage-booking-schedule', 'admin')
                        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3" id="btnOpenInstructionModal">
                            <i class="mdi mdi-plus me-1"></i> Add Instruction
                        </button>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Allowed Address Types</th>
                                    <th>Sort</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($instructions as $instr)
                                    <tr>
                                        <td class="fw-semibold">{{ $instr->title }} @if($instr->description)<div class="small text-muted">{{ $instr->description }}</div>@endif</td>
                                        <td><span class="badge bg-info-subtle text-info text-uppercase">{{ $instr->type }}</span></td>
                                        <td>
                                            @foreach (($instr->allowed_address_types ?? ['HOME','OFFICE','HOTEL']) as $at)
                                                <span class="badge bg-light text-dark border">{{ $at }}</span>
                                            @endforeach
                                        </td>
                                        <td>{{ $instr->sort_order }}</td>
                                        <td>
                                            <span class="badge {{ $instr->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $instr->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @can('manage-booking-schedule', 'admin')
                                                <button type="button" class="btn btn-sm btn-light js-edit-instr" data-instr='@json($instr)'><i class="mdi mdi-pencil"></i></button>
                                                <button type="button" class="btn btn-sm btn-warning js-toggle-instr" data-id="{{ $instr->id }}"><i class="mdi mdi-power"></i></button>
                                                <button type="button" class="btn btn-sm btn-danger js-delete-instr" data-id="{{ $instr->id }}"><i class="mdi mdi-delete"></i></button>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: AVAILABILITY SETTINGS & BLOCKED DATES -->
        <div class="tab-pane fade" id="pane-availability" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Availability Settings</h5>
                        </div>
                        <form id="availabilitySettingsForm">
                            @csrf
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small d-block">Collection Working Days</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($weekdayNames as $wd => $wName)
                                            <label class="form-check-label border rounded-3 px-3 py-1 bg-light">
                                                <input type="checkbox" class="form-check-input me-1" name="collection_weekdays[]" value="{{ $wd }}" {{ in_array($wd, $collectionWeekdays, true) ? 'checked' : '' }}>
                                                {{ $wName }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small d-block">Delivery Working Days</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($weekdayNames as $wd => $wName)
                                            <label class="form-check-label border rounded-3 px-3 py-1 bg-light">
                                                <input type="checkbox" class="form-check-input me-1" name="delivery_weekdays[]" value="{{ $wd }}" {{ in_array($wd, $deliveryWeekdays, true) ? 'checked' : '' }}>
                                                {{ $wName }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Min Notice (mins)</label>
                                        <input type="number" class="form-control" name="min_booking_notice_minutes" value="{{ $settings['min_booking_notice_minutes'] ?? 60 }}" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Max Advance Days</label>
                                        <input type="number" class="form-control" name="max_advance_days" value="{{ $settings['max_advance_days'] ?? 14 }}" min="1" max="90">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Min Turnaround (Days)</label>
                                        <input type="number" class="form-control" name="min_delivery_turnaround_days" value="{{ $settings['min_delivery_turnaround_days'] ?? 1 }}" min="1" max="14">
                                    </div>
                                </div>
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Business Timezone</label>
                                        <input type="text" class="form-control" name="timezone" value="{{ $settings['timezone'] ?? 'Europe/London' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" id="same_day_collection" name="same_day_collection" value="1" {{ ($settings['same_day_collection'] ?? '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="same_day_collection">Allow Same-Day Collection</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @can('manage-booking-schedule', 'admin')
                                <div class="card-footer bg-white text-end py-3">
                                    <button type="submit" class="btn btn-primary fw-semibold px-4">Save Availability Settings</button>
                                </div>
                            @endcan
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Blocked Dates &amp; Holidays</h5>
                        </div>
                        <div class="card-body p-4">
                            @can('manage-booking-schedule', 'admin')
                                <form id="blockedDateForm" class="row g-2 mb-3">
                                    @csrf
                                    <div class="col-5">
                                        <input type="date" class="form-control form-control-sm" name="date" required>
                                    </div>
                                    <div class="col-4">
                                        <select class="form-select form-select-sm" name="type">
                                            <option value="both">Both</option>
                                            <option value="collection">Collection</option>
                                            <option value="delivery">Delivery</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <button type="submit" class="btn btn-danger btn-sm w-100">Block</button>
                                    </div>
                                    <div class="col-12">
                                        <input type="text" class="form-control form-control-sm" name="reason" placeholder="Reason (optional, e.g. Bank Holiday)">
                                    </div>
                                </form>
                            @endcan

                            <ul class="list-group list-group-flush">
                                @forelse ($blockedDates as $bd)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <strong class="font-monospace">{{ $bd->date->format('Y-m-d') }}</strong>
                                            <span class="badge bg-secondary-subtle text-secondary ms-1 text-uppercase">{{ $bd->type }}</span>
                                            @if ($bd->reason)<div class="small text-muted">{{ $bd->reason }}</div>@endif
                                        </div>
                                        @can('manage-booking-schedule', 'admin')
                                            <button type="button" class="btn btn-sm btn-outline-danger js-delete-blocked" data-id="{{ $bd->id }}"><i class="mdi mdi-delete"></i></button>
                                        @endcan
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted small px-0">No blocked dates configured.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slot Modal -->
<div class="modal fade" id="slotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="slotModalTitle">Add Time Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="slotForm">
                @csrf
                <input type="hidden" id="slot_id" value="">
                <input type="hidden" id="slot_type" name="type" value="collection">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Start Time (24h) *</label>
                            <input type="time" class="form-control" id="slot_start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">End Time (24h) *</label>
                            <input type="time" class="form-control" id="slot_end_time" name="end_time" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Display Label (Optional)</label>
                            <input type="text" class="form-control" id="slot_label" name="label" placeholder="Auto: 20:00 - 23:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Sort Order</label>
                            <input type="number" class="form-control" id="slot_sort_order" name="sort_order" value="0" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Slot Name (Optional)</label>
                            <input type="text" class="form-control" id="slot_name" name="name" placeholder="Evening Window">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold d-block">Available Weekdays *</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($weekdayNames as $wd => $wName)
                                    <label class="form-check-label border rounded px-2 py-1 small">
                                        <input type="checkbox" class="form-check-input js-slot-wd" name="weekdays[]" value="{{ $wd }}" checked> {{ $wName }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="slot_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="slot_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Save Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Instruction Modal -->
<div class="modal fade" id="instructionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="instructionModalTitle">Add Driver Instruction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="instructionForm">
                @csrf
                <input type="hidden" id="instr_id" value="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Title *</label>
                        <input type="text" class="form-control" id="instr_title" name="title" placeholder="Collect from building reception" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description (Optional)</label>
                        <input type="text" class="form-control" id="instr_description" name="description">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Instruction Type *</label>
                            <select class="form-select" id="instr_type" name="type">
                                <option value="collection">Collection Only</option>
                                <option value="delivery">Delivery Only</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Sort Order</label>
                            <input type="number" class="form-control" id="instr_sort_order" name="sort_order" value="0" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-block">Allowed Address Types</label>
                        <div class="d-flex gap-3">
                            <label class="small"><input type="checkbox" class="form-check-input js-instr-addr" name="allowed_address_types[]" value="HOME" checked> HOME</label>
                            <label class="small"><input type="checkbox" class="form-check-input js-instr-addr" name="allowed_address_types[]" value="OFFICE" checked> OFFICE</label>
                            <label class="small"><input type="checkbox" class="form-check-input js-instr-addr" name="allowed_address_types[]" value="HOTEL" checked> HOTEL</label>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="instr_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label fw-semibold" for="instr_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Save Instruction</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

            $('.js-open-slot-modal').on('click', function () {
                const type = $(this).data('type');
                $('#slotForm')[0].reset();
                $('#slot_id').val('');
                $('#slot_type').val(type);
                $('.js-slot-wd').prop('checked', true);
                $('#slot_is_active').prop('checked', true);
                $('#slotModalTitle').text(`Add ${type.charAt(0).toUpperCase() + type.slice(1)} Time Slot`);
                $('#slotModal').modal('show');
            });

            $(document).on('click', '.js-edit-slot', function () {
                const s = $(this).data('slot');
                $('#slot_id').val(s.id);
                $('#slot_type').val(s.type);
                $('#slot_name').val(s.name || '');
                $('#slot_label').val(s.label);
                $('#slot_start_time').val(s.start_time);
                $('#slot_end_time').val(s.end_time);
                $('#slot_sort_order').val(s.sort_order);
                $('#slot_is_active').prop('checked', !!s.is_active);
                $('.js-slot-wd').each(function () {
                    $(this).prop('checked', s.weekdays.includes(parseInt($(this).val(), 10)));
                });
                $('#slotModalTitle').text('Edit Time Slot');
                $('#slotModal').modal('show');
            });

            $('#slotForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#slot_id').val();
                const url = id ? "{{ route('admin.booking-schedule.slots.update', ':id') }}".replace(':id', id) : "{{ route('admin.booking-schedule.slots.store') }}";
                $.ajax({
                    url, method: id ? 'PUT' : 'POST', data: $(this).serialize(),
                    success: (res) => { toastr.success(res.message); location.reload(); },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Error saving slot.')
                });
            });

            $('.js-toggle-slot').on('click', function () {
                $.ajax({
                    url: "{{ route('admin.booking-schedule.slots.toggle', ':id') }}".replace(':id', $(this).data('id')),
                    method: 'PATCH',
                    success: (res) => { toastr.success(res.message); location.reload(); }
                });
            });

            $('.js-delete-slot').on('click', function () {
                const id = $(this).data('id');
                Swal.fire({ title: 'Delete Slot?', icon: 'warning', showCancelButton: true }).then((r) => {
                    if (!r.isConfirmed) return;
                    $.ajax({
                        url: "{{ route('admin.booking-schedule.slots.destroy', ':id') }}".replace(':id', id),
                        method: 'DELETE',
                        success: (res) => { toastr.success(res.message); location.reload(); }
                    });
                });
            });

            $('#btnOpenInstructionModal').on('click', () => {
                $('#instructionForm')[0].reset();
                $('#instr_id').val('');
                $('.js-instr-addr').prop('checked', true);
                $('#instr_is_active').prop('checked', true);
                $('#instructionModalTitle').text('Add Driver Instruction');
                $('#instructionModal').modal('show');
            });

            $(document).on('click', '.js-edit-instr', function () {
                const i = $(this).data('instr');
                $('#instr_id').val(i.id);
                $('#instr_title').val(i.title);
                $('#instr_description').val(i.description || '');
                $('#instr_type').val(i.type);
                $('#instr_sort_order').val(i.sort_order);
                $('#instr_is_active').prop('checked', !!i.is_active);
                const allowed = i.allowed_address_types || ['HOME', 'OFFICE', 'HOTEL'];
                $('.js-instr-addr').each(function () { $(this).prop('checked', allowed.includes($(this).val())); });
                $('#instructionModalTitle').text('Edit Driver Instruction');
                $('#instructionModal').modal('show');
            });

            $('#instructionForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#instr_id').val();
                const url = id ? "{{ route('admin.booking-schedule.instructions.update', ':id') }}".replace(':id', id) : "{{ route('admin.booking-schedule.instructions.store') }}";
                $.ajax({
                    url, method: id ? 'PUT' : 'POST', data: $(this).serialize(),
                    success: (res) => { toastr.success(res.message); location.reload(); },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Error saving instruction.')
                });
            });

            $('.js-toggle-instr').on('click', function () {
                $.ajax({
                    url: "{{ route('admin.booking-schedule.instructions.toggle', ':id') }}".replace(':id', $(this).data('id')),
                    method: 'PATCH',
                    success: (res) => { toastr.success(res.message); location.reload(); }
                });
            });

            $('.js-delete-instr').on('click', function () {
                const id = $(this).data('id');
                Swal.fire({ title: 'Delete Instruction?', icon: 'warning', showCancelButton: true }).then((r) => {
                    if (!r.isConfirmed) return;
                    $.ajax({
                        url: "{{ route('admin.booking-schedule.instructions.destroy', ':id') }}".replace(':id', id),
                        method: 'DELETE',
                        success: (res) => { toastr.success(res.message); location.reload(); }
                    });
                });
            });

            $('#availabilitySettingsForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('admin.booking-schedule.settings.update') }}",
                    method: 'POST', data: $(this).serialize(),
                    success: (res) => toastr.success(res.message),
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to save settings.')
                });
            });

            $('#blockedDateForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('admin.booking-schedule.blocked-dates.store') }}",
                    method: 'POST', data: $(this).serialize(),
                    success: (res) => { toastr.success(res.message); location.reload(); },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to block date.')
                });
            });

            $('.js-delete-blocked').on('click', function () {
                $.ajax({
                    url: "{{ route('admin.booking-schedule.blocked-dates.destroy', ':id') }}".replace(':id', $(this).data('id')),
                    method: 'DELETE',
                    success: (res) => { toastr.success(res.message); location.reload(); }
                });
            });
        });
    </script>
@endpush
