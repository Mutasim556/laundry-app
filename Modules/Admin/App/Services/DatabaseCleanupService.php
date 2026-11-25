<?php

namespace Modules\Admin\App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\App\Models\DatabaseBackup;
use Modules\Admin\App\Models\DatabaseCleanupLog;
use Modules\Admin\Models\Admin;

class DatabaseCleanupService
{
    public const MODULE_BOOKING_SERVICE = 'booking_service';

    public const MODE_SAFE_TEST = 'safe_test';
    public const MODE_ALL_ELIGIBLE = 'all_eligible';

    /**
     * Strict allowlist of tables that Booking Service cleanup may delete from,
     * in exact child-to-parent dependency order (never TRUNCATE, never disable FK checks).
     *
     * @var array<int, array<string, string>>
     */
    protected array $bookingDeletableTables = [
        [
            'table' => 'booking_store_pickup_requests',
            'record_type' => 'Store Pickup Requests',
            'scope' => 'Exclusive',
            'dependency' => 'booking_id -> bookings.id, delivery_assignment_id',
            'notes' => 'Store pickup dispatch requests for completed laundry.',
        ],
        [
            'table' => 'booking_store_handovers',
            'record_type' => 'Store Handover Requests',
            'scope' => 'Exclusive',
            'dependency' => 'booking_id -> bookings.id',
            'notes' => 'Collection-to-store handover acceptance/rejection history.',
        ],
        [
            'table' => 'delivery_assignment_events',
            'record_type' => 'Delivery Job Events',
            'scope' => 'Shared (scoped by booking_id)',
            'dependency' => 'booking_id -> bookings.id, delivery_assignment_id',
            'notes' => 'Audit events for collection & final delivery assignments.',
        ],
        [
            'table' => 'delivery_assignments',
            'record_type' => 'Collection & Final Delivery Jobs',
            'scope' => 'Shared (scoped by booking_id)',
            'dependency' => 'booking_id -> bookings.id, delivery_person_id',
            'notes' => 'Collection and final delivery assignments linked to bookings.',
        ],
        [
            'table' => 'booking_store_assignments',
            'record_type' => 'Store Assignments',
            'scope' => 'Exclusive',
            'dependency' => 'booking_id -> bookings.id, store_id',
            'notes' => 'Assigned store history & store snapshot per booking.',
        ],
        [
            'table' => 'booking_collection_otps',
            'record_type' => 'Collection OTP Records',
            'scope' => 'Exclusive',
            'dependency' => 'booking_id -> bookings.id',
            'notes' => 'Customer collection verification OTPs.',
        ],
        [
            'table' => 'booking_invoices',
            'record_type' => 'Booking Invoices',
            'scope' => 'Exclusive',
            'dependency' => 'booking_id -> bookings.id',
            'notes' => 'Generated A4/thermal invoice metadata.',
        ],
        [
            'table' => 'booking_operational_items',
            'record_type' => 'Operational Service & Item Lines',
            'scope' => 'Exclusive',
            'dependency' => 'booking_id -> bookings.id',
            'notes' => 'Admin/DP adjusted service items, weights, and prices.',
        ],
        [
            'table' => 'booking_operational_categories',
            'record_type' => 'Operational Category Provenance',
            'scope' => 'Exclusive',
            'dependency' => 'booking_id -> bookings.id, item_category_id',
            'notes' => 'Active/removed category selections per booking.',
        ],
        [
            'table' => 'booking_audit_logs',
            'record_type' => 'Booking Audit History',
            'scope' => 'Exclusive',
            'dependency' => 'booking_id -> bookings.id',
            'notes' => 'Booking status transitions and edit audit logs.',
        ],
        [
            'table' => 'bookings',
            'record_type' => 'Customer Bookings & Checkout Snapshots',
            'scope' => 'Exclusive',
            'dependency' => 'Root booking table (assigned_store_id preserved)',
            'notes' => 'Core bookings including JSON snapshots and stage progress.',
        ],
    ];

    /**
     * Protected financial, settlement, and claim tables that MUST block cleanup.
     *
     * @var array<string, array<string, string>>
     */
    protected array $protectedFinancialTables = [
        'booking_customer_payments' => [
            'label' => 'Customer Payments (Stripe / PayPal / COD / Transfer)',
            'notes' => 'Confirmed, refunded, or awaiting-verification customer payments.',
        ],
        'booking_financial_settlements' => [
            'label' => 'Booking Financial Settlements',
            'notes' => 'Admin net settlement snapshots for completed bookings.',
        ],
        'admin_account_transactions' => [
            'label' => 'Admin Current Account Transactions',
            'notes' => 'Platform cash/online ledger entries.',
        ],
        'booking_store_earnings' => [
            'label' => 'Store Earnings & Commission Snapshots',
            'notes' => 'Immutable store commission and net share records.',
        ],
        'store_transactions' => [
            'label' => 'Store Account Ledger Transactions',
            'notes' => 'Store balance credits/debits.',
        ],
        'delivery_person_ledger_entries' => [
            'label' => 'Delivery Person Earnings Ledger',
            'notes' => 'Collection/final delivery person earnings and payout entries.',
        ],
        'delivery_person_cash_transactions' => [
            'label' => 'Delivery Person Cash & Transfer Ledger',
            'notes' => 'COD cash-in-hand and transfer records.',
        ],
        'customer_service_claims' => [
            'label' => 'Customer Claims / Rewash / Damage Cases',
            'notes' => 'Customer service claims and attached evidence.',
        ],
        'refund_transactions' => [
            'label' => 'Refund Transactions',
            'notes' => 'Approved, awaiting-payout, or completed customer refunds.',
        ],
    ];

    public function getEnvironmentStatus(): array
    {
        $env = app()->environment();
        $allowedEnvs = (array) config('database_cleanup.allowed_environments', ['local', 'testing']);
        $explicitEnabled = (bool) config('database_cleanup.enabled', false);

        if ($env === 'production' || ! in_array($env, $allowedEnvs, true) || ! $explicitEnabled) {
            return [
                'allowed' => false,
                'environment' => $env,
                'explicit_enabled' => $explicitEnabled,
                'database_name' => $this->getSafeDatabaseIdentifier(),
                'message' => 'Database Cleanup is disabled in this environment.',
            ];
        }

        return [
            'allowed' => true,
            'environment' => $env,
            'explicit_enabled' => true,
            'database_name' => $this->getSafeDatabaseIdentifier(),
            'message' => "Enabled for {$env} development/test environment.",
        ];
    }

    public function getSafeDatabaseIdentifier(): string
    {
        $conn = (string) config('database.default');
        $db = (string) config("database.connections.{$conn}.database", 'unknown');

        if ($conn === 'sqlite' && $db === ':memory:') {
            return 'sqlite (:memory:)';
        }

        return "{$conn}: " . basename($db);
    }

    public function verifyBackupStatus(): array
    {
        if (! config('database_cleanup.require_verified_backup', true)) {
            return [
                'verified' => true,
                'backup_id' => null,
                'backup_reference' => 'not-required',
                'verified_at' => now()->toIso8601String(),
                'message' => 'Backup verification disabled by configuration.',
            ];
        }

        if (! Schema::hasTable('database_backups')) {
            return [
                'verified' => false,
                'backup_id' => null,
                'backup_reference' => null,
                'verified_at' => null,
                'message' => 'Database backup table is unavailable. Create and verify a backup first.',
            ];
        }

        $backup = DatabaseBackup::query()
            ->where('backup_status', 'completed')
            ->whereIn('verification_status', ['archive_integrity_verified', 'restore_tested'])
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->first();

        if (! $backup) {
            return [
                'verified' => false,
                'backup_id' => null,
                'backup_reference' => null,
                'verified_at' => null,
                'message' => 'No verified database backup found. Create & verify a backup in Database Backup & Restore before cleanup.',
            ];
        }

        $disk = (string) ($backup->storage_disk ?: config('database_backups.storage_disk', 'local'));
        if (! $backup->storage_path || ! Storage::disk($disk)->exists($backup->storage_path)) {
            return [
                'verified' => false,
                'backup_id' => $backup->id,
                'backup_reference' => $backup->filename,
                'verified_at' => null,
                'message' => 'Latest verified backup archive is missing from private storage. Re-create and verify a backup first.',
            ];
        }

        return [
            'verified' => true,
            'backup_id' => $backup->id,
            'backup_reference' => $backup->filename,
            'verification_status' => $backup->verification_status,
            'verified_at' => $backup->verified_at?->toIso8601String(),
            'message' => "Verified backup ready: {$backup->filename} ({$backup->verification_status}).",
        ];
    }

    public function getModulesOverview(): array
    {
        $totalBookings = Schema::hasTable('bookings') ? (int) DB::table('bookings')->count() : 0;

        return [
            [
                'key' => self::MODULE_BOOKING_SERVICE,
                'name' => 'Booking Service',
                'description' => 'Bookings, operational items, category provenance, store assignments, handovers, pickup requests, delivery jobs, OTPs, and booking audit logs.',
                'total_bookings' => $totalBookings,
                'available' => true,
            ],
        ];
    }

    /**
     * Build a strictly read-only Booking Service inventory & dry-run preview.
     *
     * @return array<string, mixed>
     */
    public function buildBookingServicePreview(string $mode = self::MODE_SAFE_TEST, bool $issuePreviewToken = true): array
    {
        $mode = in_array($mode, [self::MODE_SAFE_TEST, self::MODE_ALL_ELIGIBLE], true)
            ? $mode
            : self::MODE_SAFE_TEST;

        $allBookings = Schema::hasTable('bookings')
            ? DB::table('bookings')->select(['id', 'reference', 'customer_email', 'payment_status', 'booking_status'])->get()
            : collect();

        $allBookingIds = $allBookings->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Determine protected bookings (any booking with financial/ledger/claim/refund dependencies)
        $blockerMap = $this->detectProtectedBookingIds($allBookings);
        $blockedIds = array_keys($blockerMap['booking_reasons']);

        // Determine candidate bookings based on selected mode
        $candidateIds = [];
        if ($mode === self::MODE_SAFE_TEST) {
            foreach ($allBookings as $b) {
                if ($this->isExplicitTestBooking($b)) {
                    $candidateIds[] = (int) $b->id;
                }
            }
        } else {
            $candidateIds = $allBookingIds;
        }

        $eligibleIds = array_values(array_diff($candidateIds, $blockedIds));
        $blockedInScopeIds = array_values(array_intersect($candidateIds, $blockedIds));

        $inventory = [];
        $totalEligibleRows = 0;

        foreach ($this->bookingDeletableTables as $meta) {
            $table = $meta['table'];
            $col = $table === 'bookings' ? 'id' : 'booking_id';
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $col)) {
                continue;
            }

            $totalRecords = (int) DB::table($table)->count();

            $relatedRecords = empty($allBookingIds)
                ? 0
                : (int) DB::table($table)->whereIn($col, $allBookingIds)->count();

            $eligibleDeleteCount = empty($eligibleIds)
                ? 0
                : (int) DB::table($table)->whereIn($col, $eligibleIds)->count();

            $totalEligibleRows += $eligibleDeleteCount;

            $inventory[] = [
                'table_name' => $table,
                'record_type' => $meta['record_type'],
                'total_records' => $totalRecords,
                'related_booking_records' => $relatedRecords,
                'eligible_delete_count' => $eligibleDeleteCount,
                'scope' => $meta['scope'],
                'dependency' => $meta['dependency'],
                'eligibility' => $eligibleDeleteCount > 0 ? 'Eligible' : ($relatedRecords > 0 ? 'Blocked / Excluded' : 'Empty'),
                'notes' => $meta['notes'],
            ];
        }

        // Add protected financial tables to inventory (always Blocked / Preserved)
        foreach ($this->protectedFinancialTables as $table => $info) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $totalRecords = (int) DB::table($table)->count();
            $relatedRecords = (! empty($allBookingIds) && Schema::hasColumn($table, 'booking_id'))
                ? (int) DB::table($table)->whereIn('booking_id', $allBookingIds)->count()
                : 0;

            $inventory[] = [
                'table_name' => $table,
                'record_type' => $info['label'],
                'total_records' => $totalRecords,
                'related_booking_records' => $relatedRecords,
                'eligible_delete_count' => 0,
                'scope' => 'Protected Financial',
                'dependency' => 'booking_id -> bookings.id',
                'eligibility' => 'Protected (Blocker)',
                'notes' => $info['notes'],
            ];
        }

        $masterCounts = $this->getPreservedMasterCounts();
        $envStatus = $this->getEnvironmentStatus();
        $backupStatus = $this->verifyBackupStatus();

        $isPartialOnly = count($blockedInScopeIds) > 0 && count($eligibleIds) > 0;
        $isFullClearBlocked = $mode === self::MODE_ALL_ELIGIBLE && count($blockedInScopeIds) > 0;
        $canExecute = $envStatus['allowed']
            && $backupStatus['verified']
            && count($eligibleIds) > 0
            && ! $isFullClearBlocked;

        $previewId = null;
        if ($issuePreviewToken) {
            $previewId = 'prv_' . Str::random(24);
            Cache::put("db_cleanup_preview:{$previewId}", [
                'preview_id' => $previewId,
                'module' => self::MODULE_BOOKING_SERVICE,
                'mode' => $mode,
                'targeted_booking_ids' => $eligibleIds,
                'blocked_booking_ids' => $blockedInScopeIds,
                'total_eligible_rows' => $totalEligibleRows,
                'environment' => $envStatus['environment'],
                'database_name' => $envStatus['database_name'],
                'created_at' => now()->toIso8601String(),
            ], now()->addSeconds((int) config('database_cleanup.preview_ttl_seconds', 600)));
        }

        return [
            'preview_id' => $previewId,
            'module' => self::MODULE_BOOKING_SERVICE,
            'mode' => $mode,
            'environment' => $envStatus,
            'backup' => $backupStatus,
            'summary' => [
                'total_bookings' => count($allBookingIds),
                'candidate_bookings' => count($candidateIds),
                'targeted_bookings' => count($eligibleIds),
                'blocked_bookings' => count($blockedInScopeIds),
                'eligible_rows_total' => $totalEligibleRows,
                'is_partial_only' => $isPartialOnly,
                'is_full_clear_blocked' => $isFullClearBlocked,
                'can_execute' => $canExecute,
            ],
            'blocker_categories' => $blockerMap['categories'],
            'inventory' => $inventory,
            'preserved_master_data' => $masterCounts,
            'confirmation_phrase' => config('database_cleanup.confirmation_phrase', 'DELETE BOOKING TEST DATA'),
        ];
    }

    /**
     * Execute an authorized, preview-verified, transactional Booking Service cleanup.
     *
     * @return array<string, mixed>
     */
    public function executeBookingServiceCleanup(
        string $previewId,
        string $confirmationPhrase,
        string $idempotencyKey,
        Admin $admin
    ): array {
        $envStatus = $this->getEnvironmentStatus();
        if (! $envStatus['allowed']) {
            throw ValidationException::withMessages([
                'environment' => [$envStatus['message']],
            ]);
        }

        $expectedPhrase = (string) config('database_cleanup.confirmation_phrase', 'DELETE BOOKING TEST DATA');
        if (trim($confirmationPhrase) !== $expectedPhrase) {
            throw ValidationException::withMessages([
                'confirmation_phrase' => ["Confirmation phrase must match \"{$expectedPhrase}\" exactly."],
            ]);
        }

        if ($idempotencyKey !== '' && Schema::hasTable('database_cleanup_logs')) {
            $existing = DatabaseCleanupLog::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                throw ValidationException::withMessages([
                    'idempotency_key' => ['This cleanup operation has already been processed.'],
                ]);
            }
        }

        $backupStatus = $this->verifyBackupStatus();
        if (! $backupStatus['verified']) {
            throw ValidationException::withMessages([
                'backup' => [$backupStatus['message']],
            ]);
        }

        $preview = Cache::get("db_cleanup_preview:{$previewId}");
        if (! is_array($preview) || ($preview['module'] ?? null) !== self::MODULE_BOOKING_SERVICE) {
            throw ValidationException::withMessages([
                'preview_id' => ['Cleanup preview is missing or expired. Please run Preview Cleanup again.'],
            ]);
        }

        $targetIds = array_values(array_unique(array_map('intval', (array) ($preview['targeted_booking_ids'] ?? []))));
        if (empty($targetIds)) {
            throw ValidationException::withMessages([
                'bookings' => ['No eligible bookings are targeted for deletion in this preview.'],
            ]);
        }

        $mode = (string) ($preview['mode'] ?? self::MODE_SAFE_TEST);
        $startedAt = now();
        $operationUuid = (string) Str::uuid();

        $lock = Cache::lock('db_cleanup_execute_lock', 30);
        if (! $lock->get()) {
            throw ValidationException::withMessages([
                'lock' => ['Another database cleanup operation is currently in progress.'],
            ]);
        }

        try {
            $masterBefore = $this->getPreservedMasterCounts();
            $deletedPerTable = [];
            $totalDeleted = 0;

            DB::transaction(function () use ($targetIds, &$deletedPerTable, &$totalDeleted) {
                // Lock targeted bookings against concurrent writes
                $lockedBookings = DB::table('bookings')
                    ->whereIn('id', $targetIds)
                    ->lockForUpdate()
                    ->get(['id', 'reference', 'customer_email', 'payment_status', 'booking_status']);

                if ($lockedBookings->count() !== count($targetIds)) {
                    throw ValidationException::withMessages([
                        'preview_id' => ['Booking records changed since the preview was generated. Please run a fresh preview.'],
                    ]);
                }

                // Revalidate zero protected financial/claim dependencies under lock
                $recheck = $this->detectProtectedBookingIds($lockedBookings);
                if (! empty($recheck['booking_reasons'])) {
                    throw ValidationException::withMessages([
                        'financial' => ['Protected financial or claim dependencies were detected during final verification. Cleanup aborted.'],
                    ]);
                }

                // Delete unconfirmed/pending/failed payment attempts only if present (never confirmed/refunded)
                if (Schema::hasTable('booking_customer_payments')) {
                    $deletedPayments = DB::table('booking_customer_payments')
                        ->whereIn('booking_id', $targetIds)
                        ->whereIn('status', ['pending', 'failed', 'cancelled'])
                        ->delete();
                    if ($deletedPayments > 0) {
                        $deletedPerTable['booking_customer_payments (unconfirmed attempts)'] = $deletedPayments;
                        $totalDeleted += $deletedPayments;
                    }
                }

                foreach ($this->bookingDeletableTables as $meta) {
                    $table = $meta['table'];
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    $col = $table === 'bookings' ? 'id' : 'booking_id';
                    $deleted = DB::table($table)->whereIn($col, $targetIds)->delete();
                    $deletedPerTable[$table] = $deleted;
                    $totalDeleted += $deleted;
                }
            });

            // Consume preview token so it cannot be replayed
            Cache::forget("db_cleanup_preview:{$previewId}");

            // Post-cleanup verification
            $masterAfter = $this->getPreservedMasterCounts();
            $remainingOrphans = 0;
            foreach ($this->bookingDeletableTables as $meta) {
                $table = $meta['table'];
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $col = $table === 'bookings' ? 'id' : 'booking_id';
                $remainingOrphans += (int) DB::table($table)->whereIn($col, $targetIds)->count();
            }

            $postVerification = [
                'orphans_remaining' => $remainingOrphans,
                'master_data_intact' => $masterBefore === $masterAfter,
                'master_counts_after' => $masterAfter,
                'verified_at' => now()->toIso8601String(),
            ];

            if (Schema::hasTable('database_cleanup_logs')) {
                DatabaseCleanupLog::create([
                    'operation_uuid' => $operationUuid,
                    'preview_id' => $previewId,
                    'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                    'module' => self::MODULE_BOOKING_SERVICE,
                    'cleanup_mode' => $mode,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name ?: $admin->email,
                    'environment' => $envStatus['environment'],
                    'database_name' => $envStatus['database_name'],
                    'backup_id' => $backupStatus['backup_id'],
                    'backup_reference' => $backupStatus['backup_reference'],
                    'targeted_bookings_count' => count($targetIds),
                    'blocked_bookings_count' => count((array) ($preview['blocked_booking_ids'] ?? [])),
                    'total_deleted_count' => $totalDeleted,
                    'deleted_per_table' => $deletedPerTable,
                    'post_verification' => $postVerification,
                    'status' => 'completed',
                    'started_at' => $startedAt,
                    'completed_at' => now(),
                ]);
            }

            return [
                'operation_uuid' => $operationUuid,
                'targeted_bookings_deleted' => $deletedPerTable['bookings'] ?? 0,
                'total_deleted_rows' => $totalDeleted,
                'deleted_per_table' => $deletedPerTable,
                'post_verification' => $postVerification,
                'backup_reference' => $backupStatus['backup_reference'],
            ];
        } catch (\Throwable $e) {
            if (! ($e instanceof ValidationException)) {
                Log::error('Database cleanup failed', [
                    'operation_uuid' => $operationUuid,
                    'error' => $e->getMessage(),
                ]);
            }
            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function isExplicitTestBooking(object $booking): bool
    {
        $ref = strtoupper((string) ($booking->reference ?? ''));
        $email = strtolower((string) ($booking->customer_email ?? ''));

        if (str_starts_with($ref, 'TEST-') || str_starts_with($ref, 'DEMO-')) {
            return true;
        }

        foreach (['@example.com', '@example.org', '@test.local', '@laundry-test.local'] as $suffix) {
            if ($email !== '' && str_ends_with($email, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $bookings
     * @return array{booking_reasons: array<int, array<int, string>>, categories: array<int, array<string, mixed>>}
     */
    protected function detectProtectedBookingIds($bookings): array
    {
        $ids = $bookings->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($ids)) {
            return ['booking_reasons' => [], 'categories' => []];
        }

        $bookingReasons = [];
        $categoryCounts = [];

        $addReason = function (int $bookingId, string $category, string $label) use (&$bookingReasons, &$categoryCounts) {
            $bookingReasons[$bookingId][] = $category;
            if (! isset($categoryCounts[$category])) {
                $categoryCounts[$category] = ['category' => $category, 'label' => $label, 'booking_ids' => []];
            }
            $categoryCounts[$category]['booking_ids'][$bookingId] = true;
        };

        // 1. Booking-level paid/refunded payment_status
        foreach ($bookings as $b) {
            $ps = strtolower((string) ($b->payment_status ?? ''));
            if (in_array($ps, ['paid', 'partial', 'partially_paid', 'refunded', 'partial_refund', 'authorized'], true)) {
                $addReason((int) $b->id, 'booking_payment_status', 'Bookings with Paid / Authorized / Refunded status');
            }
        }

        // 2. Confirmed/refunded/awaiting_verification customer payments
        if (Schema::hasTable('booking_customer_payments')) {
            $paidBookingIds = DB::table('booking_customer_payments')
                ->whereIn('booking_id', $ids)
                ->whereIn('status', ['confirmed', 'refunded', 'awaiting_verification'])
                ->distinct()
                ->pluck('booking_id');
            foreach ($paidBookingIds as $bid) {
                $addReason((int) $bid, 'booking_customer_payments', $this->protectedFinancialTables['booking_customer_payments']['label']);
            }
        }

        // 3. Other protected financial, settlement, ledger & claim tables
        foreach ($this->protectedFinancialTables as $table => $info) {
            if ($table === 'booking_customer_payments' || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'booking_id')) {
                continue;
            }
            $foundIds = DB::table($table)
                ->whereIn('booking_id', $ids)
                ->distinct()
                ->pluck('booking_id');
            foreach ($foundIds as $bid) {
                $addReason((int) $bid, $table, $info['label']);
            }
        }

        $categories = array_values(array_map(fn ($c) => [
            'category' => $c['category'],
            'label' => $c['label'],
            'affected_bookings' => count($c['booking_ids']),
        ], $categoryCounts));

        return [
            'booking_reasons' => $bookingReasons,
            'categories' => $categories,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getPreservedMasterCounts(): array
    {
        $tables = [
            'users' => 'Customer Accounts',
            'admins' => 'Admin Accounts',
            'stores' => 'Stores',
            'vendors' => 'Vendors',
            'delivery_persons' => 'Delivery Persons',
            'services' => 'Service Catalog',
            'item_categories' => 'Item Categories',
            'items' => 'Catalog Items',
            'uk_addresses' => 'UK Postcode Addresses',
            'admin_accounts' => 'Admin Current Accounts',
            'store_accounts' => 'Store Accounts',
            'store_withdrawal_requests' => 'Store Withdrawal Requests',
            'delivery_person_withdrawal_requests' => 'DP Withdrawal Requests',
        ];

        $out = [];
        foreach ($tables as $table => $label) {
            if (Schema::hasTable($table)) {
                $out[$label] = (int) DB::table($table)->count();
            }
        }

        return $out;
    }
}
