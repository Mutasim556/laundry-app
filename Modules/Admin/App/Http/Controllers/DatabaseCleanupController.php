<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Admin\App\Models\DatabaseCleanupLog;
use Modules\Admin\App\Services\DatabaseCleanupService;

class DatabaseCleanupController extends Controller
{
    public function __construct(
        protected DatabaseCleanupService $cleanupService
    ) {}

    public function index(): View
    {
        $envStatus = $this->cleanupService->getEnvironmentStatus();
        $backupStatus = $this->cleanupService->verifyBackupStatus();
        $modules = $this->cleanupService->getModulesOverview();
        $logs = Schema::hasTable('database_cleanup_logs')
            ? DatabaseCleanupLog::query()->orderByDesc('id')->limit(20)->get()
            : collect();

        return view('admin::database-cleanup.index', compact(
            'envStatus',
            'backupStatus',
            'modules',
            'logs'
        ));
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => ['required', 'string', 'in:' . DatabaseCleanupService::MODULE_BOOKING_SERVICE],
            'mode' => ['nullable', 'string', 'in:' . DatabaseCleanupService::MODE_SAFE_TEST . ',' . DatabaseCleanupService::MODE_ALL_ELIGIBLE],
        ]);

        $envStatus = $this->cleanupService->getEnvironmentStatus();
        $mode = (string) ($validated['mode'] ?? DatabaseCleanupService::MODE_SAFE_TEST);

        $preview = $this->cleanupService->buildBookingServicePreview(
            $mode,
            (bool) $envStatus['allowed']
        );

        return response()->json([
            'success' => true,
            'data' => $preview,
        ]);
    }

    public function execute(Request $request): JsonResponse
    {
        $envStatus = $this->cleanupService->getEnvironmentStatus();
        if (! $envStatus['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $envStatus['message'],
            ], 403);
        }

        $validated = $request->validate([
            'module' => ['required', 'string', 'in:' . DatabaseCleanupService::MODULE_BOOKING_SERVICE],
            'preview_id' => ['required', 'string', 'max:80'],
            'confirmation_phrase' => ['required', 'string', 'max:80'],
            'idempotency_key' => ['required', 'string', 'max:80'],
        ]);

        /** @var \Modules\Admin\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $result = $this->cleanupService->executeBookingServiceCleanup(
                $validated['preview_id'],
                $validated['confirmation_phrase'],
                $validated['idempotency_key'],
                $admin
            );

            $freshPreview = $this->cleanupService->buildBookingServicePreview(
                DatabaseCleanupService::MODE_SAFE_TEST,
                true
            );

            return response()->json([
                'success' => true,
                'message' => "Deleted {$result['targeted_bookings_deleted']} eligible booking(s) ({$result['total_deleted_rows']} total rows) safely.",
                'result' => $result,
                'preview' => $freshPreview,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Cleanup validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
