@extends('admin::layouts.app')

@section('title', 'Contact Enquiries')

@push('styles')
<style>
    .adm-enq-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 55%, #10b981 100%);
        border-radius: 1rem;
        padding: 1.5rem 1.75rem;
        color: #ffffff;
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.18);
    }
    .adm-enq-kpi-card {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }
    .adm-enq-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
    }
    .adm-enq-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="adm-enq-hero mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <span class="badge bg-white text-primary fw-semibold px-3 py-1 rounded-pill mb-2">Website Manager</span>
            <h3 class="fw-bold mb-1 text-white">Contact Enquiries</h3>
            <p class="mb-0 text-white-50">Review customer messages, track status, and record internal support notes.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.website-manager.contact.index') }}" class="btn btn-light rounded-pill px-3 fw-semibold">
                <i data-feather="edit-3" class="me-1" style="width: 15px; height: 15px;"></i> Contact Page CMS
            </a>
            <a href="{{ route('contact') }}" target="_blank" rel="noopener" class="btn btn-outline-light rounded-pill px-3 fw-semibold">
                <i data-feather="external-link" class="me-1" style="width: 15px; height: 15px;"></i> View Contact Page
            </a>
        </div>
    </div>

    {{-- 4 KPI Cards in a single line --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card adm-enq-kpi-card h-100 adm-enq-kpi-filter" data-status="">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total Enquiries</div>
                        <div class="fs-3 fw-bold mt-1" id="kpiTotal">{{ $kpis['total'] }}</div>
                    </div>
                    <div class="adm-enq-kpi-icon bg-primary-subtle text-primary">
                        <i data-feather="inbox"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card adm-enq-kpi-card h-100 adm-enq-kpi-filter" data-status="new">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">New</div>
                        <div class="fs-3 fw-bold mt-1 text-primary" id="kpiNew">{{ $kpis['new'] }}</div>
                    </div>
                    <div class="adm-enq-kpi-icon bg-info-subtle text-info">
                        <i data-feather="bell"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card adm-enq-kpi-card h-100 adm-enq-kpi-filter" data-status="in_progress">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">In Progress</div>
                        <div class="fs-3 fw-bold mt-1 text-warning" id="kpiInProgress">{{ $kpis['in_progress'] }}</div>
                    </div>
                    <div class="adm-enq-kpi-icon bg-warning-subtle text-warning">
                        <i data-feather="clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card adm-enq-kpi-card h-100 adm-enq-kpi-filter" data-status="resolved">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Resolved</div>
                        <div class="fs-3 fw-bold mt-1 text-success" id="kpiResolved">{{ $kpis['resolved'] }}</div>
                    </div>
                    <div class="adm-enq-kpi-icon bg-success-subtle text-success">
                        <i data-feather="check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter & Table Card --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 pb-2 px-4">
            <form id="admEnqFilterForm" class="row g-2 align-items-center" method="GET" action="{{ route('admin.website-manager.enquiries.index') }}">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i data-feather="search" style="width: 15px; height: 15px;"></i></span>
                        <input
                            type="text"
                            name="search"
                            id="admEnqSearchInput"
                            class="form-control border-start-0"
                            value="{{ $search }}"
                            placeholder="Search reference, name, email, or subject..."
                        >
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <select name="status" id="admEnqStatusSelect" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="new" @selected($activeStatus === 'new')>New</option>
                        <option value="read" @selected($activeStatus === 'read')>Read</option>
                        <option value="in_progress" @selected($activeStatus === 'in_progress')>In Progress</option>
                        <option value="resolved" @selected($activeStatus === 'resolved')>Resolved</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Filter</button>
                    <button type="button" id="admEnqResetBtn" class="btn btn-outline-secondary rounded-pill px-3">Reset</button>
                </div>
            </form>
        </div>
        <div class="card-body px-4 pb-4" id="admEnqTableContainer">
            @include('admin::website-manager.partials.enquiries-table', ['enquiries' => $enquiries])
        </div>
    </div>
</div>

{{-- Enquiry Detail & Status Modal --}}
<div class="modal fade" id="admEnqModal" tabindex="-1" aria-labelledby="admEnqModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #4f46e5, #0ea5e9);">
                <div>
                    <span class="badge bg-white text-primary fw-semibold mb-1" id="admEnqModalRef">ENQ-</span>
                    <h5 class="modal-title fw-bold text-white" id="admEnqModalTitle">Enquiry Details</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="admEnqModalAlert" class="alert d-none mb-3" role="alert"></div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <div class="small text-muted">Customer</div>
                            <div class="fw-bold" id="admEnqModalName">-</div>
                            <div class="small" id="admEnqModalEmail">-</div>
                            <div class="small text-muted" id="admEnqModalPhone"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <div class="small text-muted">Submission Info</div>
                            <div class="small mt-1"><strong>Submitted:</strong> <span id="admEnqModalSubmitted">-</span></div>
                            <div class="small"><strong>Booking Ref:</strong> <span id="admEnqModalBooking">-</span></div>
                            <div class="small"><strong>Email Alert:</strong> <span id="admEnqModalMailStatus">-</span></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted fw-semibold mb-1">Subject</div>
                    <div class="fw-semibold fs-6" id="admEnqModalSubject">-</div>
                </div>

                <div class="mb-4">
                    <div class="small text-muted fw-semibold mb-1">Message</div>
                    <div class="p-3 border rounded-3 bg-white" style="white-space: pre-wrap;" id="admEnqModalMessage">-</div>
                </div>

                <form id="admEnqUpdateForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="admEnqStatusField" class="form-label fw-semibold">Status</label>
                            <select id="admEnqStatusField" name="status" class="form-select">
                                <option value="new">New</option>
                                <option value="read">Read</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="admEnqNotesField" class="form-label fw-semibold">Internal Notes</label>
                            <textarea id="admEnqNotesField" name="internal_notes" class="form-control" rows="3" maxlength="3000" placeholder="Add internal follow-up notes..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" id="admEnqSaveStatusBtn" class="btn btn-primary rounded-pill px-4">
                    Save Status &amp; Notes
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function($) {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const indexUrl = @json(route('admin.website-manager.enquiries.index'));
    const modalEl = document.getElementById('admEnqModal');
    const modalInstance = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    let currentUpdateUrl = null;

    function refreshIcons() {
        if (window.feather) {
            window.feather.replace();
        }
    }

    function updateKpis(kpis) {
        if (!kpis) return;
        $('#kpiTotal').text(kpis.total ?? 0);
        $('#kpiNew').text(kpis.new ?? 0);
        $('#kpiInProgress').text(kpis.in_progress ?? 0);
        $('#kpiResolved').text(kpis.resolved ?? 0);
    }

    function fetchEnquiries(url) {
        $.ajax({
            url: url || indexUrl,
            method: 'GET',
            data: url ? undefined : $('#admEnqFilterForm').serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.table_html !== undefined) {
                    $('#admEnqTableContainer').html(res.table_html);
                    updateKpis(res.kpis);
                    refreshIcons();
                }
            }
        });
    }

    $('#admEnqFilterForm').on('submit', function(e) {
        e.preventDefault();
        fetchEnquiries();
    });

    $('#admEnqStatusSelect').on('change', function() {
        fetchEnquiries();
    });

    $('#admEnqResetBtn').on('click', function() {
        $('#admEnqSearchInput').val('');
        $('#admEnqStatusSelect').val('');
        fetchEnquiries();
    });

    $('.adm-enq-kpi-filter').on('click', function() {
        const status = $(this).data('status') || '';
        $('#admEnqStatusSelect').val(status);
        fetchEnquiries();
    });

    $(document).on('click', '.adm-enq-pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (href) {
            fetchEnquiries(href);
        }
    });

    $(document).on('click', '.adm-enq-view-btn', function() {
        const showUrl = $(this).data('show-url');
        currentUpdateUrl = $(this).data('update-url');
        $('#admEnqModalAlert').addClass('d-none').removeClass('alert-success alert-danger').text('');

        $.ajax({
            url: showUrl,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                const e = res.enquiry || {};
                $('#admEnqModalRef').text(e.reference || '');
                $('#admEnqModalName').text(e.name || '-');
                $('#admEnqModalEmail').text(e.email || '-');
                $('#admEnqModalPhone').text(e.phone || '');
                $('#admEnqModalSubmitted').text(e.submitted_at || '-');
                $('#admEnqModalBooking').text(e.booking_reference || 'None');
                $('#admEnqModalMailStatus').text(e.email_notification_status || 'pending');
                $('#admEnqModalSubject').text(e.subject || '-');
                $('#admEnqModalMessage').text(e.message || '-');
                $('#admEnqStatusField').val(e.status || 'read');
                $('#admEnqNotesField').val(e.internal_notes || '');
                if (modalInstance) {
                    modalInstance.show();
                }
                fetchEnquiries();
            }
        });
    });

    $('#admEnqSaveStatusBtn').on('click', function() {
        if (!currentUpdateUrl) return;
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: currentUpdateUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                status: $('#admEnqStatusField').val(),
                internal_notes: $('#admEnqNotesField').val()
            },
            success: function(res) {
                $('#admEnqModalAlert')
                    .removeClass('d-none alert-danger')
                    .addClass('alert-success')
                    .text(res.message || 'Enquiry updated.');
                fetchEnquiries();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to update enquiry.';
                $('#admEnqModalAlert')
                    .removeClass('d-none alert-success')
                    .addClass('alert-danger')
                    .text(msg);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
