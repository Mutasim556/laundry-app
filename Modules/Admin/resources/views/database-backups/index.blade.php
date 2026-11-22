@extends('admin::layouts.app')

@push('styles')
<style>
    .db-page {
        background-color: #F6F8FC;
        border-radius: 16px;
        color: #171717;
    }
    .db-header-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        border: 1px solid #FDE68A;
        border-left: 5px solid #FFC629;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(255, 198, 41, 0.12);
    }
    .db-stat-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.9rem 1rem;
        height: 100%;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    .db-stat-card--blue { background: #EFF6FF; border-color: #93C5FD; }
    .db-stat-card--amber { background: #FFF8E5; border-color: #FCD34D; }
    .db-stat-card--green { background: #ECFDF5; border-color: #86EFAC; }
    .db-stat-card--purple { background: #F5F3FF; border-color: #C4B5FD; }
    .db-stat-card--cyan { background: #ECFEFF; border-color: #67E8F9; }
    .db-stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #475569;
    }
    .db-stat-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: #171717;
        margin-top: 0.25rem;
        word-break: break-word;
    }
    .db-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    }
    .db-btn-yellow {
        background-color: #FFC629;
        border-color: #FFC629;
        color: #171717;
        font-weight: 600;
        border-radius: 10px;
    }
    .db-btn-yellow:hover, .db-btn-yellow:focus {
        background-color: #f0b516;
        border-color: #f0b516;
        color: #171717;
    }
    .db-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #475569;
        background-color: #F8FAFC;
        white-space: nowrap;
    }
    .db-table td {
        font-size: 0.84rem;
        color: #171717;
        vertical-align: middle;
    }
    .db-filename {
        font-family: "Courier New", Courier, monospace;
        font-weight: 700;
        font-size: 0.8rem;
        word-break: break-all;
    }
    .db-action-btn {
        height: 30px;
        padding: 0 0.55rem;
        border-radius: 8px;
        font-size: 0.76rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 db-page">
    <!-- Header -->
    <div class="db-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width: 36px; height: 36px; background-color: #FFC629; color: #171717;">
                        <i class="mdi mdi-database font-20"></i>
                    </span>
                    <div>
                        <h3 class="fw-bold mb-0" style="color: #171717;">Database Backup &amp; Restore</h3>
                        <div class="small" style="color: #475569;">Create, download, verify, and safely restore database backups.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('create-database-backups', 'admin')
                    <button type="button" class="btn btn-sm db-btn-yellow px-3 py-2" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                        <i class="mdi mdi-database me-1"></i> Create Backup
                    </button>
                @endcan
                @if(auth('admin')->user()?->can('create-database-backups', 'admin') || auth('admin')->user()?->can('restore-database-backups', 'admin'))
                    <button type="button" class="btn btn-sm btn-outline-primary px-3 py-2" style="border-radius: 10px; font-weight: 600;" data-bs-toggle="modal" data-bs-target="#uploadBackupModal">
                        <i class="mdi mdi-upload me-1"></i> Upload Backup
                    </button>
                @endif
                @can('restore-database-backups', 'admin')
                    @if($latestBackup)
                        <button type="button"
                                class="btn btn-sm btn-outline-danger px-3 py-2 js-open-restore-modal"
                                style="border-radius: 10px; font-weight: 600;"
                                data-backup-id="{{ $latestBackup->id }}"
                                data-filename="{{ $latestBackup->filename }}"
                                data-date="{{ $latestBackup->created_at?->format('d M Y, H:i') }}"
                                data-size="{{ $latestBackup->formatted_size }}"
                                data-db="{{ $latestBackup->database_name }}"
                                data-version="{{ $latestBackup->db_engine }} {{ $latestBackup->db_version }}"
                                data-verification="{{ $latestBackup->verification_label }}">
                            <i class="mdi mdi-restore me-1"></i> Restore Database
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 py-2 px-3 small mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 5 Summary Cards -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-3">
        <div class="col">
            <div class="db-stat-card db-stat-card--blue">
                <div class="db-stat-label">Current Database</div>
                <div class="db-stat-value">{{ $capabilities['database'] ?: 'N/A' }}</div>
                <div class="small mt-1" style="color: #475569;">{{ $capabilities['engine'] }} {{ $capabilities['version'] }}</div>
            </div>
        </div>
        <div class="col">
            <div class="db-stat-card db-stat-card--amber">
                <div class="db-stat-label">Total Backups</div>
                <div class="db-stat-value">{{ $totalBackups }}</div>
                <div class="small mt-1" style="color: #475569;">Protected private storage</div>
            </div>
        </div>
        <div class="col">
            <div class="db-stat-card db-stat-card--green">
                <div class="db-stat-label">Latest Successful Backup</div>
                <div class="db-stat-value">{{ $latestBackup ? $latestBackup->created_at?->format('d M Y, H:i') : 'None yet' }}</div>
                <div class="small text-truncate mt-1" style="color: #475569;">{{ $latestBackup?->filename ?? 'Create your first backup' }}</div>
            </div>
        </div>
        <div class="col">
            <div class="db-stat-card db-stat-card--purple">
                <div class="db-stat-label">Total Backup Storage</div>
                <div class="db-stat-value">
                    {{ $totalStorageBytes >= 1048576 ? number_format($totalStorageBytes / 1048576, 2) . ' MB' : number_format($totalStorageBytes / 1024, 2) . ' KB' }}
                </div>
                <div class="small mt-1" style="color: #475569;">{{ $capabilities['encrypt_at_rest'] ? 'Encrypted at rest' : 'Gzip compressed' }}</div>
            </div>
        </div>
        <div class="col">
            <div class="db-stat-card db-stat-card--cyan">
                <div class="db-stat-label">Last Verified Backup</div>
                <div class="db-stat-value">{{ $lastVerifiedBackup ? $lastVerifiedBackup->verified_at?->format('d M Y, H:i') : 'Unverified' }}</div>
                <div class="small mt-1" style="color: #475569;">{{ $lastVerifiedBackup?->verification_label ?? 'Run Verify on a backup' }}</div>
            </div>
        </div>
    </div>

    <!-- Backup History Table -->
    <div class="db-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h6 class="fw-bold mb-0" style="color: #171717;"><i class="mdi mdi-history me-1 text-primary"></i>Backup History</h6>

            <form method="GET" action="{{ route('admin.database-backups.index') }}" class="d-flex flex-wrap gap-2">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width: 190px;" placeholder="Search filename or DB...">
                <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm" style="width: 145px;">
                <select name="status" class="form-select form-select-sm" style="width: 130px;">
                    <option value="">All Status</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                </select>
                <button type="submit" class="btn btn-sm btn-dark px-3">Filter</button>
                @if(request()->hasAny(['q', 'date', 'status']))
                    <a href="{{ route('admin.database-backups.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 db-table">
                <thead>
                    <tr>
                        <th>Backup Filename</th>
                        <th>Created Date &amp; Time</th>
                        <th>Database</th>
                        <th>File Size</th>
                        <th>Backup Type</th>
                        <th>Verification Status</th>
                        <th>Created By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $bk)
                        @php
                            $vBadge = match($bk->verification_status) {
                                'restore_tested' => 'background-color: #DCFCE7; color: #166534;',
                                'archive_integrity_verified' => 'background-color: #DBEAFE; color: #1D4ED8;',
                                'failed' => 'background-color: #FEE2E2; color: #B91C1C;',
                                default => 'background-color: #FEF3C7; color: #92400E;',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="db-filename">{{ $bk->filename }}</div>
                                <div class="small" style="color: #475569; font-size: 0.72rem;">
                                    {{ $bk->backup_method }}
                                    @if($bk->is_protected)
                                        &middot; <span class="badge bg-warning-subtle text-warning-emphasis">Protected</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $bk->created_at?->format('d M Y, H:i:s') }}</td>
                            <td>
                                <span class="fw-semibold">{{ $bk->database_name }}</span>
                                @if($bk->db_engine)
                                    <div class="small" style="color: #475569; font-size: 0.72rem;">{{ $bk->db_engine }} {{ $bk->db_version }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap fw-semibold">{{ $bk->formatted_size }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $bk->type_label }}</span></td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1" style="{{ $vBadge }}">
                                    {{ $bk->verification_label }}
                                </span>
                            </td>
                            <td>{{ $bk->createdByAdmin?->name ?? 'System / Scheduler' }}</td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex align-items-center gap-1">
                                    @can('download-database-backups', 'admin')
                                        @if($bk->backup_status === 'completed')
                                            <a href="{{ route('admin.database-backups.download', $bk) }}" class="btn btn-outline-primary db-action-btn" title="Download">
                                                <i class="mdi mdi-download"></i><span>Download</span>
                                            </a>
                                        @endif
                                    @endcan

                                    @if($bk->backup_status === 'completed')
                                        <button type="button"
                                                class="btn btn-outline-secondary db-action-btn js-verify-backup"
                                                data-url="{{ route('admin.database-backups.verify', $bk) }}">
                                            <i class="mdi mdi-check-circle"></i><span>Verify</span>
                                        </button>
                                    @endif

                                    @can('restore-database-backups', 'admin')
                                        @if($bk->backup_status === 'completed')
                                            <button type="button"
                                                    class="btn btn-outline-danger db-action-btn js-open-restore-modal"
                                                    data-backup-id="{{ $bk->id }}"
                                                    data-filename="{{ $bk->filename }}"
                                                    data-date="{{ $bk->created_at?->format('d M Y, H:i') }}"
                                                    data-size="{{ $bk->formatted_size }}"
                                                    data-db="{{ $bk->database_name }}"
                                                    data-version="{{ $bk->db_engine }} {{ $bk->db_version }}"
                                                    data-verification="{{ $bk->verification_label }}">
                                                <i class="mdi mdi-restore"></i><span>Restore</span>
                                            </button>
                                        @endif
                                    @endcan

                                    @can('delete-database-backups', 'admin')
                                        @if(!$bk->is_protected)
                                            <button type="button"
                                                    class="btn btn-outline-danger db-action-btn js-open-delete-modal"
                                                    data-url="{{ route('admin.database-backups.destroy', $bk) }}"
                                                    data-filename="{{ $bk->filename }}">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4" style="color: #475569;">No database backups found yet. Click <strong>Create Backup</strong> to generate your first archive.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($backups->hasPages())
            <div class="mt-3">{{ $backups->links() }}</div>
        @endif
    </div>

    <!-- Schedule, Environment Capabilities & Operation History -->
    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="db-card p-3 p-md-4 h-100">
                <h6 class="fw-bold mb-3" style="color: #171717;"><i class="mdi mdi-clock me-1 text-warning"></i>Scheduled Backups &amp; Environment</h6>

                <div class="p-2 px-3 rounded-3 mb-3 small" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="color: #475569;">mysqldump Tool</span>
                        <span class="fw-semibold {{ $capabilities['mysqldump_path'] ? 'text-success' : 'text-warning-emphasis' }}">
                            {{ $capabilities['mysqldump_path'] ? 'Available' : 'PDO Fallback' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span style="color: #475569;">mysql CLI Tool</span>
                        <span class="fw-semibold {{ $capabilities['mysql_path'] ? 'text-success' : 'text-warning-emphasis' }}">
                            {{ $capabilities['mysql_path'] ? 'Available' : 'PDO Fallback' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span style="color: #475569;">Queue Driver</span>
                        <span class="fw-semibold">{{ strtoupper($queueConnection) }}</span>
                    </div>
                </div>

                @can('manage-database-backup-schedules', 'admin')
                    <form method="POST" action="{{ route('admin.database-backups.schedule.update') }}" class="small">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-1">Automatic Schedule</label>
                                <select name="frequency" class="form-select form-select-sm">
                                    <option value="manual" @selected($scheduleConfig['frequency'] === 'manual')>Manual Only</option>
                                    <option value="daily" @selected($scheduleConfig['frequency'] === 'daily')>Daily (02:00)</option>
                                    <option value="weekly" @selected($scheduleConfig['frequency'] === 'weekly')>Weekly (Sunday 02:00)</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold mb-1">Keep Last</label>
                                <input type="number" name="keep_count" value="{{ $scheduleConfig['keep_count'] }}" min="1" max="100" class="form-control form-control-sm">
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold mb-1">Keep Days</label>
                                <input type="number" name="keep_days" value="{{ $scheduleConfig['keep_days'] }}" min="1" max="365" class="form-control form-control-sm">
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold mb-1">Max MB</label>
                                <input type="number" name="max_storage_mb" value="{{ $scheduleConfig['max_storage_mb'] }}" min="50" max="51200" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-between align-items-center gap-2">
                            <span class="text-muted" style="font-size: 0.74rem;">Requires Laravel Scheduler (`schedule:run`) on server.</span>
                            <button type="submit" class="btn btn-sm db-btn-yellow px-3">Save Policy</button>
                        </div>
                    </form>
                @endcan
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="db-card p-3 p-md-4 h-100">
                <h6 class="fw-bold mb-3" style="color: #171717;"><i class="mdi mdi-backup-restore me-1 text-purple"></i>Recent Backup &amp; Restore Operations</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 db-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Admin</th>
                                <th>Operation</th>
                                <th>Status</th>
                                <th>Details / Safety Backup</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($operations as $op)
                                <tr>
                                    <td class="text-nowrap" style="font-size: 0.78rem;">{{ $op->created_at?->format('d M Y, H:i') }}</td>
                                    <td>{{ $op->admin?->name ?? 'Scheduler' }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $op->operation_type) }}</td>
                                    <td>
                                        @php
                                            $opStyle = match($op->operation_status) {
                                                'completed' => 'background-color: #DCFCE7; color: #166534;',
                                                'failed' => 'background-color: #FEE2E2; color: #B91C1C;',
                                                default => 'background-color: #DBEAFE; color: #1D4ED8;',
                                            };
                                        @endphp
                                        <span class="badge rounded-pill" style="{{ $opStyle }}">{{ $op->status_label }}</span>
                                    </td>
                                    <td style="font-size: 0.78rem;">
                                        @if($op->failure_summary)
                                            <span class="text-danger">{{ $op->failure_summary }}</span>
                                        @elseif($op->safetyBackup)
                                            <span>Safety: <code>{{ $op->safetyBackup->filename }}</code></span>
                                        @else
                                            <span>{{ $op->verification_result ?: ($op->backup?->filename ?? '-') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3" style="color: #475569;">No backup operations logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header bg-white border-bottom py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width: 34px; height: 34px; background: #FFC629; color: #171717;">
                        <i class="mdi mdi-database"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Create Backup</h6>
                        <div class="small" style="color: #475569; font-size: 0.78rem;">Generate a compressed, verified database archive.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createBackupForm" method="POST" action="{{ route('admin.database-backups.store') }}">
                @csrf
                <div class="modal-body p-3" style="background-color: #F6F8FC;">
                    <div id="createBackupError" class="alert alert-danger small d-none mb-2"></div>
                    <div class="p-3 rounded-3 bg-white border small">
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #475569;">Database</span>
                            <strong style="color: #171717;">{{ $capabilities['database'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #475569;">Backup Type</span>
                            <strong style="color: #171717;">Full Database Backup (.sql.gz)</strong>
                        </div>
                        <div class="small pt-2 border-top" style="color: #475569;">
                            Includes table structures, data, indexes, foreign keys, views, triggers, routines, and events. Stored in protected private storage.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3">
                    <button type="button" class="btn btn-sm btn-light px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnConfirmCreateBackup" class="btn btn-sm db-btn-yellow px-3">Create Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Backup Modal -->
<div class="modal fade" id="uploadBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header bg-white border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">Upload Backup File</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.database-backups.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-3" style="background-color: #F6F8FC;">
                    <label class="form-label small fw-semibold">Select Backup Archive (.sql or .sql.gz)</label>
                    <input type="file" name="backup_file" accept=".sql,.gz,.sql.gz" class="form-control" required>
                    <div class="form-text small">Uploaded files are validated, compressed, checksummed, and stored in private storage before restore.</div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3">
                    <button type="button" class="btn btn-sm btn-light px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm db-btn-yellow px-3">Upload &amp; Verify</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Database Modal -->
<div class="modal fade" id="restoreBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; max-height: 92vh;">
            <div class="modal-header bg-white border-bottom py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width: 34px; height: 34px; background: #FEE2E2; color: #B91C1C;">
                        <i class="mdi mdi-alert"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-danger">Restore Database</h6>
                        <div class="small" style="color: #475569; font-size: 0.78rem;">A fresh pre-restore safety backup is automatically created first.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="restoreBackupForm" method="POST" style="display: flex; flex-direction: column; min-height: 0;">
                @csrf
                <div class="modal-body p-3" style="background-color: #F6F8FC; overflow-y: auto;">
                    <div class="alert alert-danger py-2 px-3 small mb-3">
                        <strong>Warning:</strong> Restoring a backup may replace current database data. Changes made after the backup may be lost. SQL imports are not automatically transactional; if a restore fails, use the recorded pre-restore safety backup for manual recovery.
                    </div>

                    <div id="restoreErrors" class="alert alert-danger small d-none mb-2"></div>

                    <div class="p-3 rounded-3 bg-white border small mb-3">
                        <div class="d-flex justify-content-between mb-1"><span style="color: #475569;">Selected Backup</span><strong id="restoreMetaFilename" class="db-filename"></strong></div>
                        <div class="d-flex justify-content-between mb-1"><span style="color: #475569;">Backup Date</span><span id="restoreMetaDate"></span></div>
                        <div class="d-flex justify-content-between mb-1"><span style="color: #475569;">File Size</span><span id="restoreMetaSize"></span></div>
                        <div class="d-flex justify-content-between mb-1"><span style="color: #475569;">Source Database</span><span id="restoreMetaDb"></span></div>
                        <div class="d-flex justify-content-between mb-1"><span style="color: #475569;">Database Version</span><span id="restoreMetaVersion"></span></div>
                        <div class="d-flex justify-content-between"><span style="color: #475569;">Verification Status</span><strong id="restoreMetaVerification"></strong></div>
                    </div>

                    <div id="restoreProgressBox" class="p-3 rounded-3 bg-white border small d-none mb-3">
                        <div class="fw-bold mb-1">Restore Stage: <span id="restoreStageText" class="text-primary">Pending</span></div>
                        <div id="restoreSafetyInfo" class="small text-muted"></div>
                        <div id="restoreOutcomeText" class="small mt-1"></div>
                    </div>

                    <div id="restoreConfirmInputs">
                        <div class="form-check mb-2 small">
                            <input class="form-check-input" type="checkbox" name="acknowledge_overwrite" value="1" id="ackOverwrite" required>
                            <label class="form-check-label fw-semibold" for="ackOverwrite">
                                I understand this replaces live data in <strong>{{ $capabilities['database'] }}</strong> and a safety backup will run first.
                            </label>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Type <code>RESTORE DATABASE</code> to confirm <span class="text-danger">*</span></label>
                            <input type="text" name="confirmation_phrase" class="form-control form-control-sm" placeholder="RESTORE DATABASE" required>
                        </div>
                        <div class="mb-1">
                            <label class="form-label small fw-semibold mb-1">Administrator Password <span class="text-danger">*</span></label>
                            <input type="password" name="admin_password" class="form-control form-control-sm" placeholder="Enter your admin password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3">
                    <button type="button" class="btn btn-sm btn-light px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnConfirmRestore" class="btn btn-sm btn-danger px-3">Start Safe Restore</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Backup Modal -->
<div class="modal fade" id="deleteBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header bg-white border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0 text-danger">Delete Backup</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteBackupForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-3 small" style="background-color: #F6F8FC;">
                    Are you sure you want to permanently delete <strong id="deleteBackupFilename" class="db-filename"></strong>? This action is audited and cannot be undone.
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3">
                    <button type="button" class="btn btn-sm btn-light px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger px-3">Delete Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#createBackupForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btnConfirmCreateBackup').prop('disabled', true).text('Creating...');
        $('#createBackupError').addClass('d-none').empty();

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function () {
                window.location.reload();
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Backup creation failed.';
                $('#createBackupError').removeClass('d-none').text(msg);
                $btn.prop('disabled', false).text('Create Backup');
            }
        });
    });

    $(document).on('click', '.js-verify-backup', function () {
        const $btn = $(this).prop('disabled', true);
        $.ajax({
            url: $btn.data('url'),
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || @json(csrf_token())
            },
            success: function () {
                window.location.reload();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Verification failed.');
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.js-open-delete-modal', function () {
        $('#deleteBackupForm').attr('action', $(this).data('url'));
        $('#deleteBackupFilename').text($(this).data('filename'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteBackupModal')).show();
    });

    let restorePollTimer = null;
    $(document).on('click', '.js-open-restore-modal', function () {
        const id = $(this).data('backup-id');
        $('#restoreBackupForm').attr('action', @json(url('/admin/database-backups')) + '/' + id + '/restore');
        $('#restoreMetaFilename').text($(this).data('filename'));
        $('#restoreMetaDate').text($(this).data('date'));
        $('#restoreMetaSize').text($(this).data('size'));
        $('#restoreMetaDb').text($(this).data('db'));
        $('#restoreMetaVersion').text($(this).data('version') || 'Unknown');
        $('#restoreMetaVerification').text($(this).data('verification'));
        $('#restoreErrors').addClass('d-none').empty();
        $('#restoreProgressBox').addClass('d-none');
        $('#restoreConfirmInputs').removeClass('d-none');
        $('#btnConfirmRestore').prop('disabled', false).text('Start Safe Restore');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('restoreBackupModal')).show();
    });

    $('#restoreBackupForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btnConfirmRestore').prop('disabled', true).text('Restoring...');
        $('#restoreErrors').addClass('d-none').empty();
        $('#restoreProgressBox').removeClass('d-none');
        $('#restoreStageText').text('Pending');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function (res) {
                $('#restoreConfirmInputs').addClass('d-none');
                pollOperationStatus(res.operation_id);
            },
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors;
                let msg = xhr.responseJSON?.message || 'Restore failed to start.';
                if (errs) msg = Object.values(errs).map(a => a[0]).join(' ');
                $('#restoreErrors').removeClass('d-none').text(msg);
                $btn.prop('disabled', false).text('Start Safe Restore');
            }
        });
    });

    function pollOperationStatus(opId) {
        if (!opId) return;
        const url = @json(url('/admin/database-backups/operations')) + '/' + opId + '/status';
        $.getJSON(url, function (res) {
            $('#restoreStageText').text(res.status_label || res.operation_status);
            if (res.safety_backup_filename) {
                $('#restoreSafetyInfo').text('Safety Backup: ' + res.safety_backup_filename);
            }
            if (res.operation_status === 'completed') {
                $('#restoreOutcomeText').html('<span class="text-success fw-semibold">' + (res.verification_result || 'Restore completed.') + '</span>');
                setTimeout(() => window.location.reload(), 1200);
            } else if (res.operation_status === 'failed') {
                $('#restoreOutcomeText').html('<span class="text-danger fw-semibold">' + (res.failure_summary || 'Restore failed.') + '</span>');
                $('#btnConfirmRestore').prop('disabled', false).text('Retry');
            } else {
                restorePollTimer = setTimeout(() => pollOperationStatus(opId), 1000);
            }
        });
    }
});
</script>
@endpush
