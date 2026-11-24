@extends('admin::layouts.app')

@push('styles')
<style>
    .dc-page {
        background-color: #F6F8FC;
        border-radius: 16px;
        color: #171717;
    }
    .dc-header-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        border: 1px solid #FDE68A;
        border-left: 5px solid #FFC629;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(255, 198, 41, 0.12);
    }
    .dc-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    }
    .dc-module-card {
        background: #FFFFFF;
        border: 2px solid #FFC629;
        border-radius: 14px;
        box-shadow: 0 3px 12px rgba(255, 198, 41, 0.14);
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .dc-module-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(255, 198, 41, 0.22);
    }
    .dc-badge-yellow {
        background-color: #FFF8E5;
        color: #92400E;
        border: 1px solid #FDE68A;
    }
    .dc-btn-yellow {
        background-color: #FFC629;
        border-color: #FFC629;
        color: #171717;
        font-weight: 700;
        border-radius: 10px;
    }
    .dc-btn-yellow:hover {
        background-color: #f0b516;
        border-color: #f0b516;
        color: #171717;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="dc-page p-3 p-md-4">
        <!-- Header -->
        <div class="dc-header-card p-3 p-md-4 mb-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width: 36px; height: 36px; background: #FFC629; color: #171717;">
                            <i class="mdi mdi-delete-sweep fs-5"></i>
                        </span>
                        <h1 class="h4 fw-bold mb-0" style="color: #171717;">Database Cleanup</h1>
                    </div>
                    <p class="mb-0 small" style="color: #64748B;">
                        Review and safely clear eligible development data by module.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill px-3 py-2 fw-semibold {{ $envStatus['allowed'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                        Env: {{ strtoupper($envStatus['environment']) }} ({{ $envStatus['allowed'] ? 'Enabled' : 'Disabled' }})
                    </span>
                    <span class="badge rounded-pill px-3 py-2 fw-semibold dc-badge-yellow">
                        DB: {{ $envStatus['database_name'] }}
                    </span>
                </div>
            </div>
        </div>

        @if (!$envStatus['allowed'])
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 fw-semibold" role="alert">
                <i class="mdi mdi-shield-alert me-1"></i>
                Database Cleanup is disabled in this environment.
                <span class="fw-normal d-block small mt-1">
                    Only approved local/test environments with explicit <code>DB_CLEANUP_ENABLED=true</code> configuration may execute cleanup operations.
                </span>
            </div>
        @endif

        <!-- Environment & Backup Status Row -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="dc-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-uppercase" style="color: #64748B;">Safety Gate</span>
                        <span class="badge rounded-pill {{ $envStatus['allowed'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                            {{ $envStatus['allowed'] ? 'Approved Local/Test' : 'Blocked' }}
                        </span>
                    </div>
                    <div class="fw-semibold small" style="color: #171717;">{{ $envStatus['message'] }}</div>
                    <div class="small mt-1" style="color: #64748B;">Database: <strong>{{ $envStatus['database_name'] }}</strong></div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="dc-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-uppercase" style="color: #64748B;">Backup Verification</span>
                        <span id="dcBackupBadge" class="badge rounded-pill {{ $backupStatus['verified'] ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                            {{ $backupStatus['verified'] ? 'Verified' : 'Required' }}
                        </span>
                    </div>
                    <div id="dcBackupMessage" class="fw-semibold small" style="color: #171717;">{{ $backupStatus['message'] }}</div>
                    @if (!$backupStatus['verified'])
                        <a href="{{ route('admin.database-backups.index') }}" class="btn btn-sm dc-btn-yellow mt-2">
                            Go to Database Backup &amp; Restore
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Module Cards -->
        <div class="row g-3 mb-3">
            @foreach ($modules as $mod)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="dc-module-card p-3 h-100 js-select-cleanup-module" data-module="{{ $mod['key'] }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width: 34px; height: 34px; background: #FFF8E5; color: #B45309; border: 1px solid #FDE68A;">
                                    <i class="mdi mdi-clipboard-check-outline fs-5"></i>
                                </span>
                                <div>
                                    <div class="fw-bold" style="color: #171717;">{{ $mod['name'] }}</div>
                                    <div class="small" style="color: #64748B;">{{ $mod['total_bookings'] }} total bookings</div>
                                </div>
                            </div>
                            <span class="badge rounded-pill dc-badge-yellow">Active Module</span>
                        </div>
                        <p class="small mb-0" style="color: #475569;">{{ $mod['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Detail Section -->
        <div class="dc-card p-3 p-md-4 mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 pb-3 mb-3 border-bottom">
                <div>
                    <h2 class="h5 fw-bold mb-1" style="color: #171717;">Booking Service — Schema Inventory &amp; Dry-Run Preview</h2>
                    <div class="small" style="color: #64748B;">Inspect related tables, scoped counts, protected financial blockers, and preserved master data before any deletion.</div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <select id="dcCleanupMode" class="form-select form-select-sm" style="width: auto; min-width: 240px;">
                        <option value="safe_test">Option A: Clear Safe Test Bookings</option>
                        <option value="all_eligible">Option B: Clear All Eligible Booking Data</option>
                    </select>
                    <button type="button" id="btnDcPreview" class="btn btn-sm dc-btn-yellow px-3 py-2">
                        <i class="mdi mdi-magnify me-1"></i> Preview Cleanup
                    </button>
                </div>
            </div>

            <!-- Alerts / Results -->
            <div id="dcAlertBox" class="alert d-none rounded-3 small fw-semibold mb-3" role="alert"></div>

            <!-- Preview Summary Metrics -->
            <div id="dcSummaryRow" class="row g-2 mb-3 d-none">
                <div class="col-6 col-md-3">
                    <div class="p-2 px-3 rounded-3 border bg-light">
                        <div class="small" style="color: #64748B;">Total Bookings</div>
                        <div id="dcStatTotal" class="fs-5 fw-bold" style="color: #171717;">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 px-3 rounded-3 border" style="background-color: #EFF6FF; border-color: #BFDBFE !important;">
                        <div class="small" style="color: #1D4ED8;">Targeted Bookings</div>
                        <div id="dcStatTargeted" class="fs-5 fw-bold" style="color: #1D4ED8;">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 px-3 rounded-3 border" style="background-color: #FEF3C7; border-color: #FDE68A !important;">
                        <div class="small" style="color: #92400E;">Blocked (Protected)</div>
                        <div id="dcStatBlocked" class="fs-5 fw-bold" style="color: #92400E;">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 px-3 rounded-3 border" style="background-color: #FEF2F2; border-color: #FECACA !important;">
                        <div class="small" style="color: #B91C1C;">Eligible Rows to Delete</div>
                        <div id="dcStatRows" class="fs-5 fw-bold" style="color: #B91C1C;">0</div>
                    </div>
                </div>
            </div>

            <!-- Blockers Panel -->
            <div id="dcBlockersPanel" class="alert alert-warning rounded-3 small d-none mb-3">
                <div class="fw-bold mb-1"><i class="mdi mdi-shield-lock me-1"></i> Protected Financial Dependencies Detected</div>
                <div id="dcBlockersList"></div>
            </div>

            <!-- Table Inventory -->
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase" style="color: #475569;">
                            <th>Table Name</th>
                            <th>Record Type</th>
                            <th class="text-end">Total Records</th>
                            <th class="text-end">Related Booking</th>
                            <th class="text-end">Eligible Delete</th>
                            <th>Shared / Exclusive</th>
                            <th>Dependency</th>
                            <th>Eligibility</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="dcInventoryBody" class="small">
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Loading Booking Service table inventory...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Preserved Master Data -->
            <div id="dcMasterWrap" class="mt-3 pt-3 border-top d-none">
                <div class="small fw-bold mb-2" style="color: #16A34A;">
                    <i class="mdi mdi-check-decagram me-1"></i> Preserved Master &amp; Account Records (Never Deleted)
                </div>
                <div id="dcMasterChips" class="d-flex flex-wrap gap-2"></div>
            </div>

            <!-- Bottom Actions -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3 border-top">
                <div class="small" style="color: #64748B;">
                    Full resets should use a disposable database restore rather than web-based bulk deletion.
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" id="btnDcPreviewBottom" class="btn dc-btn-yellow px-3 py-2">
                        Preview Cleanup
                    </button>
                    <button type="button" id="btnDcOpenConfirm" class="btn btn-danger fw-bold px-3 py-2" style="border-radius: 10px;" disabled>
                        <i class="mdi mdi-delete-alert me-1"></i> Clear Booking Test Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Cleanup History -->
        <div class="dc-card p-3 p-md-4">
            <h3 class="h6 fw-bold mb-3" style="color: #171717;">Cleanup Audit History</h3>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>When</th>
                            <th>Operator</th>
                            <th>Module / Mode</th>
                            <th>Env / DB</th>
                            <th>Backup Ref</th>
                            <th class="text-end">Bookings</th>
                            <th class="text-end">Total Deleted</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="dcHistoryBody">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->completed_at?->format('d M Y, H:i') ?? $log->created_at?->format('d M Y, H:i') }}</td>
                                <td>{{ $log->admin_name ?? ('Admin #' . $log->admin_id) }}</td>
                                <td>{{ $log->module }} <span class="text-muted">({{ $log->cleanup_mode }})</span></td>
                                <td>{{ $log->environment }} / {{ $log->database_name }}</td>
                                <td class="text-muted">{{ $log->backup_reference ?? '-' }}</td>
                                <td class="text-end fw-semibold">{{ $log->targeted_bookings_count }}</td>
                                <td class="text-end fw-bold text-danger">{{ $log->total_deleted_count }}</td>
                                <td><span class="badge bg-success-subtle text-success">{{ ucfirst($log->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">No cleanup operations recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="dcConfirmModal" tabindex="-1" aria-labelledby="dcConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom" style="background: #FEF2F2;">
                <h5 class="modal-title fw-bold text-danger" id="dcConfirmModalLabel">
                    <i class="mdi mdi-alert-octagon me-1"></i> Confirm Database Cleanup
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body small">
                <div class="p-3 rounded-3 mb-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Module:</span><strong>Booking Service</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Bookings Targeted:</span><strong id="dcModalTargeted">0</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Eligible Related Rows:</span><strong id="dcModalRows">0</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Protected Records Blocked:</span><strong id="dcModalBlocked">0</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Environment / DB:</span><strong id="dcModalEnvDb">-</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Backup Status:</span><strong id="dcModalBackup">-</strong></div>
                </div>
                <p class="text-danger fw-semibold mb-2">
                    This will permanently delete the targeted disposable booking records and their eligible child rows in dependency order. Master accounts and protected financial records will not be touched.
                </p>
                <label for="dcPhraseInput" class="form-label fw-semibold">
                    Type <code>DELETE BOOKING TEST DATA</code> to confirm:
                </label>
                <input type="text" id="dcPhraseInput" class="form-control" placeholder="DELETE BOOKING TEST DATA" autocomplete="off">
                <div id="dcModalError" class="alert alert-danger py-2 px-3 mt-2 mb-0 d-none"></div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnDcExecuteConfirm" class="btn btn-danger fw-bold" disabled>
                    Clear Booking Test Data
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const previewUrl = "{{ route('admin.database-cleanup.preview') }}";
    const executeUrl = "{{ route('admin.database-cleanup.execute') }}";

    const modeSelect = document.getElementById('dcCleanupMode');
    const btnPreview = document.getElementById('btnDcPreview');
    const btnPreviewBottom = document.getElementById('btnDcPreviewBottom');
    const btnOpenConfirm = document.getElementById('btnDcOpenConfirm');
    const alertBox = document.getElementById('dcAlertBox');
    const summaryRow = document.getElementById('dcSummaryRow');
    const blockersPanel = document.getElementById('dcBlockersPanel');
    const blockersList = document.getElementById('dcBlockersList');
    const inventoryBody = document.getElementById('dcInventoryBody');
    const masterWrap = document.getElementById('dcMasterWrap');
    const masterChips = document.getElementById('dcMasterChips');

    const modalEl = document.getElementById('dcConfirmModal');
    const phraseInput = document.getElementById('dcPhraseInput');
    const btnExecuteConfirm = document.getElementById('btnDcExecuteConfirm');
    const modalError = document.getElementById('dcModalError');

    let currentPreview = null;
    let isSubmitting = false;

    const showAlert = (type, msg) => {
        alertBox.className = `alert alert-${type} rounded-3 small fw-semibold mb-3`;
        alertBox.textContent = msg;
        alertBox.classList.remove('d-none');
    };

    const renderPreview = (data) => {
        currentPreview = data;
        const s = data.summary || {};

        summaryRow.classList.remove('d-none');
        document.getElementById('dcStatTotal').textContent = s.total_bookings ?? 0;
        document.getElementById('dcStatTargeted').textContent = s.targeted_bookings ?? 0;
        document.getElementById('dcStatBlocked').textContent = s.blocked_bookings ?? 0;
        document.getElementById('dcStatRows').textContent = s.eligible_rows_total ?? 0;

        if ((data.blocker_categories || []).length > 0) {
            blockersPanel.classList.remove('d-none');
            blockersList.innerHTML = data.blocker_categories.map(b =>
                `<div>&bull; <strong>${b.label}</strong>: ${b.affected_bookings} booking(s) protected</div>`
            ).join('');
        } else {
            blockersPanel.classList.add('d-none');
            blockersList.innerHTML = '';
        }

        if (s.is_full_clear_blocked) {
            showAlert('warning', 'Full clear (Option B) is blocked because protected financial/claim records exist. Switch to Option A (Safe Test Bookings) or use an isolated disposable database.');
        } else if (s.is_partial_only) {
            showAlert('info', 'Partial cleanup only: protected bookings are excluded and will remain untouched.');
        } else if (!data.environment?.allowed) {
            showAlert('danger', data.environment?.message || 'Database Cleanup is disabled in this environment.');
        } else if (!data.backup?.verified) {
            showAlert('warning', data.backup?.message || 'Verified backup required before cleanup.');
        } else {
            alertBox.classList.add('d-none');
        }

        inventoryBody.innerHTML = (data.inventory || []).map(row => {
            const badgeClass = row.eligibility === 'Eligible'
                ? 'bg-danger-subtle text-danger'
                : (row.eligibility.startsWith('Protected') ? 'bg-warning-subtle text-warning' : 'bg-light text-muted border');
            return `<tr>
                <td class="fw-semibold text-nowrap"><code>${row.table_name}</code></td>
                <td>${row.record_type}</td>
                <td class="text-end">${row.total_records}</td>
                <td class="text-end">${row.related_booking_records}</td>
                <td class="text-end fw-bold ${row.eligible_delete_count > 0 ? 'text-danger' : ''}">${row.eligible_delete_count}</td>
                <td><span class="badge bg-light text-dark border">${row.scope}</span></td>
                <td class="text-muted">${row.dependency}</td>
                <td><span class="badge ${badgeClass}">${row.eligibility}</span></td>
                <td class="text-muted">${row.notes}</td>
            </tr>`;
        }).join('');

        const master = data.preserved_master_data || {};
        masterWrap.classList.remove('d-none');
        masterChips.innerHTML = Object.entries(master).map(([label, count]) =>
            `<span class="badge bg-light text-dark border px-2 py-1">${label}: <strong>${count}</strong></span>`
        ).join('');

        btnOpenConfirm.disabled = !s.can_execute;
    };

    const loadPreview = async () => {
        btnPreview.disabled = true;
        btnPreviewBottom.disabled = true;
        btnOpenConfirm.disabled = true;

        try {
            const res = await fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    module: 'booking_service',
                    mode: modeSelect.value,
                }),
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Unable to load preview.');
            }
            renderPreview(json.data);
        } catch (err) {
            showAlert('danger', err.message || 'Failed to load cleanup preview.');
        } finally {
            btnPreview.disabled = false;
            btnPreviewBottom.disabled = false;
        }
    };

    btnPreview.addEventListener('click', loadPreview);
    btnPreviewBottom.addEventListener('click', loadPreview);
    modeSelect.addEventListener('change', loadPreview);

    btnOpenConfirm.addEventListener('click', () => {
        if (!currentPreview || !currentPreview.summary?.can_execute || !window.bootstrap) return;
        document.getElementById('dcModalTargeted').textContent = currentPreview.summary.targeted_bookings;
        document.getElementById('dcModalRows').textContent = currentPreview.summary.eligible_rows_total;
        document.getElementById('dcModalBlocked').textContent = currentPreview.summary.blocked_bookings;
        document.getElementById('dcModalEnvDb').textContent = `${currentPreview.environment.environment} / ${currentPreview.environment.database_name}`;
        document.getElementById('dcModalBackup').textContent = currentPreview.backup.backup_reference || 'Verified';
        phraseInput.value = '';
        btnExecuteConfirm.disabled = true;
        modalError.classList.add('d-none');
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    phraseInput.addEventListener('input', () => {
        const expected = currentPreview?.confirmation_phrase || 'DELETE BOOKING TEST DATA';
        btnExecuteConfirm.disabled = phraseInput.value.trim() !== expected || isSubmitting;
    });

    btnExecuteConfirm.addEventListener('click', async () => {
        if (isSubmitting || !currentPreview?.preview_id) return;
        isSubmitting = true;
        btnExecuteConfirm.disabled = true;
        btnExecuteConfirm.textContent = 'Executing Cleanup...';
        modalError.classList.add('d-none');

        const idemp = 'dc_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);

        try {
            const res = await fetch(executeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    module: 'booking_service',
                    preview_id: currentPreview.preview_id,
                    confirmation_phrase: phraseInput.value.trim(),
                    idempotency_key: idemp,
                }),
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Cleanup failed.');
            }

            window.bootstrap.Modal.getInstance(modalEl)?.hide();
            showAlert('success', json.message + ' Post-cleanup verification passed.');
            if (json.preview) {
                renderPreview(json.preview);
            } else {
                await loadPreview();
            }
        } catch (err) {
            modalError.textContent = err.message || 'Cleanup execution failed.';
            modalError.classList.remove('d-none');
        } finally {
            isSubmitting = false;
            btnExecuteConfirm.textContent = 'Clear Booking Test Data';
            const expected = currentPreview?.confirmation_phrase || 'DELETE BOOKING TEST DATA';
            btnExecuteConfirm.disabled = phraseInput.value.trim() !== expected;
        }
    });

    // Initial read-only inventory load
    loadPreview();
})();
</script>
@endpush
