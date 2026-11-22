<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Jobs\RestoreDatabaseJob;
use Modules\Admin\App\Models\DatabaseBackup;
use Modules\Admin\App\Models\DatabaseBackupOperation;
use Modules\Admin\App\Services\DatabaseBackupService;
use Modules\Admin\Models\Setting;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DatabaseBackupController extends Controller
{
    public function __construct(
        protected DatabaseBackupService $backupService
    ) {}

    public function index(Request $request)
    {
        $capabilities = $this->backupService->getEnvironmentCapabilities();

        $query = DatabaseBackup::query()->with('createdByAdmin:id,name,email')->orderByDesc('created_at');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                    ->orWhere('database_name', 'like', "%{$search}%");
            });
        }

        if ($status = trim((string) $request->query('status', ''))) {
            $query->where('backup_status', $status);
        }

        if ($date = trim((string) $request->query('date', ''))) {
            $query->whereDate('created_at', $date);
        }

        $backups = $query->paginate(12)->withQueryString();

        $allCompleted = DatabaseBackup::query()->where('backup_status', 'completed');
        $totalBackups = (clone $allCompleted)->count();
        $latestBackup = (clone $allCompleted)->orderByDesc('created_at')->first();
        $totalStorageBytes = (int) (clone $allCompleted)->sum('file_size_bytes');
        $lastVerifiedBackup = (clone $allCompleted)
            ->whereIn('verification_status', ['archive_integrity_verified', 'restore_tested'])
            ->orderByDesc('verified_at')
            ->first();

        $operations = DatabaseBackupOperation::query()
            ->with(['admin:id,name', 'backup:id,filename', 'safetyBackup:id,filename'])
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $scheduleConfig = [
            'frequency' => (string) Setting::get('db_backup_schedule_frequency', 'manual'),
            'keep_count' => (int) Setting::get('db_backup_keep_count', config('database_backups.retention_keep_count', 10)),
            'keep_days' => (int) Setting::get('db_backup_keep_days', config('database_backups.retention_keep_days', 30)),
            'max_storage_mb' => (int) Setting::get('db_backup_max_storage_mb', config('database_backups.max_storage_mb', 1024)),
        ];

        $queueConnection = (string) config('queue.default', 'sync');

        return view('admin::database-backups.index', compact(
            'capabilities',
            'backups',
            'totalBackups',
            'latestBackup',
            'totalStorageBytes',
            'lastVerifiedBackup',
            'operations',
            'scheduleConfig',
            'queueConnection'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $adminId = Auth::guard('admin')->id();
            $backup = $this->backupService->createBackup(
                $adminId ? (int) $adminId : null,
                'full',
                $request->ip(),
                $request->userAgent()
            );

            $msg = "Backup \"{$backup->filename}\" created and verified ({$backup->formatted_size}).";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'backup_id' => $backup->id])
                : back()->with('success', $msg);
        } catch (Throwable $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }
    }

    public function upload(Request $request): JsonResponse|RedirectResponse
    {
        $maxKb = (int) config('database_backups.max_upload_kb', 102400);
        $request->validate([
            'backup_file' => ['required', 'file', "max:{$maxKb}"],
        ]);

        try {
            $adminId = Auth::guard('admin')->id();
            $backup = $this->backupService->storeUploadedBackup(
                $request->file('backup_file'),
                $adminId ? (int) $adminId : null,
                $request->ip(),
                $request->userAgent()
            );

            $msg = "Uploaded backup \"{$backup->filename}\" validated and stored securely.";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'backup_id' => $backup->id])
                : back()->with('success', $msg);
        } catch (Throwable $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }
    }

    public function download(Request $request, DatabaseBackup $backup): StreamedResponse|RedirectResponse
    {
        try {
            $archiveBytes = $this->backupService->getDecryptedArchiveBytes($backup);
            $safeFilename = basename((string) $backup->filename);

            DatabaseBackupOperation::create([
                'admin_id' => Auth::guard('admin')->id(),
                'operation_type' => 'download_backup',
                'database_backup_id' => $backup->id,
                'source_database' => $backup->database_name,
                'operation_status' => 'completed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ? Str::limit($request->userAgent(), 250) : null,
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            return response()->streamDownload(function () use ($archiveBytes) {
                echo $archiveBytes;
            }, $safeFilename, [
                'Content-Type' => 'application/gzip',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function verify(Request $request, DatabaseBackup $backup): JsonResponse|RedirectResponse
    {
        try {
            $adminId = Auth::guard('admin')->id();
            $verified = $this->backupService->verifyBackup(
                $backup,
                $adminId ? (int) $adminId : null,
                $request->ip(),
                $request->userAgent(),
                true
            );

            $msg = "Backup \"{$verified->filename}\" status: {$verified->verification_label}.";

            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => $msg,
                    'verification_status' => $verified->verification_status,
                    'verification_label' => $verified->verification_label,
                    'verification_notes' => $verified->verification_notes,
                ])
                : back()->with('success', $msg);
        } catch (Throwable $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }
    }

    public function restore(Request $request, DatabaseBackup $backup): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'confirmation_phrase' => ['required', 'string'],
            'admin_password' => ['required', 'string'],
            'acknowledge_overwrite' => ['accepted'],
        ]);

        if (trim($validated['confirmation_phrase']) !== 'RESTORE DATABASE') {
            $msg = 'You must type RESTORE DATABASE exactly to confirm restoration.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => ['confirmation_phrase' => [$msg]]], 422)
                : back()->withErrors(['confirmation_phrase' => $msg]);
        }

        $admin = Auth::guard('admin')->user();
        if (! $admin || ! Hash::check($validated['admin_password'], $admin->password)) {
            $msg = 'Administrator password verification failed.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => ['admin_password' => [$msg]]], 422)
                : back()->withErrors(['admin_password' => $msg]);
        }

        try {
            $operation = $this->backupService->initiateRestore(
                $backup,
                (int) $admin->id,
                $request->ip(),
                $request->userAgent()
            );

            RestoreDatabaseJob::dispatch($operation->id);

            $freshOp = $operation->fresh(['safetyBackup:id,filename'])
                ?? DatabaseBackupOperation::with('safetyBackup:id,filename')
                    ->where('operation_type', 'restore_database')
                    ->latest('id')
                    ->firstOrFail();

            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Restore operation initiated.',
                    'operation_id' => $freshOp->id,
                    'operation_status' => $freshOp->operation_status,
                    'status_label' => $freshOp->status_label,
                    'safety_backup_filename' => $freshOp->safetyBackup?->filename,
                    'failure_summary' => $freshOp->failure_summary,
                ])
                : back()->with('success', "Restore operation #{$freshOp->id} status: {$freshOp->status_label}");
        } catch (Throwable $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }
    }

    public function operationStatus(DatabaseBackupOperation $operation): JsonResponse
    {
        $operation->loadMissing(['safetyBackup:id,filename']);

        return response()->json([
            'success' => true,
            'operation_id' => $operation->id,
            'operation_status' => $operation->operation_status,
            'status_label' => $operation->status_label,
            'verification_result' => $operation->verification_result,
            'failure_summary' => $operation->failure_summary,
            'safety_backup_filename' => $operation->safetyBackup?->filename,
            'completed_at' => $operation->completed_at?->format('d M Y, H:i:s'),
        ]);
    }

    public function destroy(Request $request, DatabaseBackup $backup): JsonResponse|RedirectResponse
    {
        try {
            $adminId = Auth::guard('admin')->id();
            $this->backupService->deleteBackup(
                $backup,
                $adminId ? (int) $adminId : null,
                $request->ip(),
                $request->userAgent()
            );

            $msg = "Backup \"{$backup->filename}\" deleted safely.";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $msg])
                : back()->with('success', $msg);
        } catch (Throwable $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'frequency' => ['required', Rule::in(['manual', 'daily', 'weekly'])],
            'keep_count' => ['required', 'integer', 'min:1', 'max:100'],
            'keep_days' => ['required', 'integer', 'min:1', 'max:365'],
            'max_storage_mb' => ['required', 'integer', 'min:50', 'max:51200'],
        ]);

        Setting::set('db_backup_schedule_frequency', $validated['frequency']);
        Setting::set('db_backup_keep_count', (string) $validated['keep_count']);
        Setting::set('db_backup_keep_days', (string) $validated['keep_days']);
        Setting::set('db_backup_max_storage_mb', (string) $validated['max_storage_mb']);

        DatabaseBackupOperation::create([
            'admin_id' => Auth::guard('admin')->id(),
            'operation_type' => 'update_schedule',
            'operation_status' => 'completed',
            'verification_result' => "Schedule updated: {$validated['frequency']}, keep {$validated['keep_count']} backups / {$validated['keep_days']} days / {$validated['max_storage_mb']} MB",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ? Str::limit($request->userAgent(), 250) : null,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Backup schedule and retention policy saved.');
    }
}
